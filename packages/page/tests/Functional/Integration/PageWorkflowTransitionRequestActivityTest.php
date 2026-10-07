<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Every action on a request ends in an activity, so the log shows it and the notifier can send it.
 */
#[CoversNothing]
class PageWorkflowTransitionRequestActivityTest extends SuluTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();
    }

    public function testApproveStoresAnActivityForTheActingUser(): void
    {
        $id = $this->createPageInReview('review');
        $reviewer = $this->createReviewer();

        $this->act($this->createReviewerClient(), $id, 'approve', ['comment' => 'Looks good']);

        $activity = $this->findActivity('workflow_transition_request.approved');
        $this->assertSame('pages', $activity->getResourceKey());
        $this->assertSame($id, $activity->getResourceId());
        $this->assertSame('en', $activity->getResourceLocale());
        $this->assertSame('sulu-io', $activity->getResourceWebspaceKey());
        $this->assertSame('Page In Review', $activity->getResourceTitle());
        $this->assertSame(['comment' => 'Looks good'], $activity->getContext());
        $this->assertSame($reviewer->getId(), $activity->getUser()?->getId());
    }

    public function testRejectStoresAnActivityWithTheComment(): void
    {
        $id = $this->createPageInReview('review');
        $reviewer = $this->createReviewer();

        $this->act($this->createReviewerClient(), $id, 'reject', ['comment' => 'Not yet']);

        $activity = $this->findActivity('workflow_transition_request.rejected');
        $this->assertSame($id, $activity->getResourceId());
        $this->assertSame(['comment' => 'Not yet'], $activity->getContext());
        $this->assertSame($reviewer->getId(), $activity->getUser()?->getId());
    }

    public function testRetryStoresAnActivityWithTheValidatorKey(): void
    {
        $id = $this->createPageInReview('review-validated');

        $this->act($this->client, $id, 'retry', [], ['validator' => 'test_configured_result']);

        $activity = $this->findActivity('workflow_transition_request.validation_retried');
        $this->assertSame($id, $activity->getResourceId());
        $this->assertSame(['validatorKey' => 'test_configured_result'], $activity->getContext());
        $this->assertSame(self::getTestUser()->getId(), $activity->getUser()?->getId());
    }

    /**
     * The validation run settles its rows with a statement that bypasses the unit of work, so the
     * activity only reaches the database because the run is flushed after it.
     */
    public function testValidationRunStoresAnActivityWithTheCounts(): void
    {
        $this->createPageInReview('review-validated');

        $activity = $this->findActivity('workflow_transition_request.validated');
        $this->assertSame('Page In Review', $activity->getResourceTitle());
        $this->assertSame(['approved' => 0, 'rejected' => 1], $activity->getContext());
    }

    public function testRetriedValidationRunStoresAnotherActivity(): void
    {
        $id = $this->createPageInReview('review-validated');

        $this->act($this->client, $id, 'retry', [], ['validator' => 'test_configured_result']);

        $this->assertCount(2, $this->findActivities('workflow_transition_request.validated'));
    }

    /**
     * @param array<string, string> $body
     * @param array<string, string> $query
     */
    private function act(KernelBrowser $client, string $pageId, string $action, array $body = [], array $query = []): void
    {
        $request = self::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class)->getOneBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $pageId,
            'active' => true,
        ]);

        $client->request(
            'POST',
            \sprintf(
                '/admin/api/workflow-transition-requests/%s.json?%s',
                $request->getId(),
                \http_build_query(['action' => $action] + $query),
            ),
            $body,
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    /**
     * The creator of a request cannot review it, so the verdicts come from a second user.
     */
    private function createReviewer(): User
    {
        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Page Reviewer');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(255);
        $permission->setContext(PageAdmin::getPageSecurityContext('sulu-io'));
        $entityManager->persist($permission);

        $contact = new Contact();
        $contact->setFirstName('Page');
        $contact->setLastName('Reviewer');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername('pagereviewer');
        $user->setSalt('');
        $user->setLocale('en');
        $user->setEmail('pagereviewer@test.com');
        $user->setContact($contact);
        $user->setPassword(self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user)->hash('test'));
        $entityManager->persist($user);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale((string) \json_encode(['en']));
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();

        return $user;
    }

    private function createReviewerClient(): KernelBrowser
    {
        self::ensureKernelShutdown();

        return $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => 'pagereviewer',
                'PHP_AUTH_PW' => 'test',
            ],
        );
    }

    /**
     * @return list<ActivityInterface>
     */
    private function findActivities(string $type): array
    {
        self::getEntityManager()->clear();

        /** @var list<ActivityInterface> $activities */
        $activities = self::getEntityManager()->getRepository(ActivityInterface::class)->findBy(['type' => $type]);

        return $activities;
    }

    private function findActivity(string $type): ActivityInterface
    {
        $activities = $this->findActivities($type);
        $this->assertCount(1, $activities, \sprintf('Expected one "%s" activity.', $type));

        return $activities[0];
    }

    private function createPageInReview(string $template): string
    {
        $homepage = new Page('0199ee04-c220-784e-a6fa-ac985870f2d5');
        $homepage->setLft(0);
        $homepage->setRgt(1);
        $homepage->setDepth(0);
        $homepage->setWebspaceKey('sulu-io');
        self::getEntityManager()->persist($homepage);
        self::getEntityManager()->flush();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/pages?locale=en&parentId=%s&webspace=sulu-io', $homepage->getId()),
            [],
            [],
            [],
            (string) \json_encode(['template' => $template, 'title' => 'Page In Review', 'url' => '/page-in-review']),
        );
        $this->assertHttpStatusCode(201, $this->client->getResponse());

        /** @var array{id: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $id = $content['id'];

        $this->client->request('POST', \sprintf('/admin/api/pages/%s?locale=en&action=request_for_review', $id));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        return $id;
    }
}
