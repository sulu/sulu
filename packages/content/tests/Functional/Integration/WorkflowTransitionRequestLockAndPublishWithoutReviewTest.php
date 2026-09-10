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

namespace Sulu\Content\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The admin saves a form with a PUT and triggers a workflow action with a payload-less POST, so these
 * cases drive the review lock and publishing without review over HTTP.
 */
#[CoversNothing]
class WorkflowTransitionRequestLockAndPublishWithoutReviewTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private KernelBrowser $client;

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );

        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);

        $this->authenticateAsRequestCreator();
    }

    public function testSaveDraftBlockedWhenActiveRequestExists(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->client->request(
            'PUT',
            \sprintf('/admin/api/examples/%d?locale=en', $example->getId()),
            [],
            [],
            [],
            (string) \json_encode([
                'template' => self::REVIEW_TEMPLATE,
                'title' => 'Updated While Locked',
                'url' => '/updated-while-locked',
            ]),
        );

        $response = $this->client->getResponse();
        $this->assertSame(
            409,
            $response->getStatusCode(),
            \sprintf(
                'Expected 409 for PUT during active request but got %d. Body: %s',
                $response->getStatusCode(),
                (string) $response->getContent(),
            ),
        );
    }

    public function testCopyLocaleIntoReviewedLocaleIsRejected(): void
    {
        $example = static::createExample([
            'en' => ['draft' => ['template' => self::REVIEW_TEMPLATE, 'title' => 'English Draft', 'url' => '/english-draft']],
            'de' => ['draft' => ['template' => self::REVIEW_TEMPLATE, 'title' => 'German Draft', 'url' => '/german-draft']],
        ]);
        static::getEntityManager()->flush();

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?locale=en&action=copy_locale&src=de&dest=en', $example->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        static::getEntityManager()->clear();
        $this->assertNotNull(
            $this->workflowTransitionRequestRepository->findOneBy([
                'resourceKey' => Example::RESOURCE_KEY,
                'resourceId' => (string) $example->getId(),
                'locale' => 'en',
                'active' => true,
            ]),
            'The refused copy must leave the review open.',
        );

        $this->client->request('GET', \sprintf('/admin/api/examples/%d?locale=en', $example->getId()));

        /** @var array{title: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('English Draft', $content['title'], 'The locked draft must not be overwritten.');
    }

    public function testRestoreIntoReviewedLocaleIsRejected(): void
    {
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        // publishing writes the version the restore below reaches for
        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
        );
        static::getEntityManager()->flush();

        $version = $this->findLatestVersion($example);

        // the draft change is what a restore would overwrite, and it takes the content out of
        // `published` so it can go to review
        $this->contentManager->persist(
            $example,
            ['template' => self::REVIEW_TEMPLATE, 'title' => 'Reviewed Draft', 'url' => '/draft-title'],
            $dimensionAttributes,
        );
        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?locale=en&action=restore&version=%d', $example->getId(), $version),
        );

        $response = $this->client->getResponse();
        $this->assertSame(409, $response->getStatusCode(), (string) $response->getContent());

        static::getEntityManager()->clear();
        $this->assertNotNull(
            $this->workflowTransitionRequestRepository->findOneBy([
                'resourceKey' => Example::RESOURCE_KEY,
                'resourceId' => (string) $example->getId(),
                'locale' => 'en',
                'active' => true,
            ]),
            'The refused restore must leave the review open.',
        );

        $this->client->request('GET', \sprintf('/admin/api/examples/%d?locale=en', $example->getId()));

        /** @var array{title: string} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame('Reviewed Draft', $content['title'], 'The locked draft must not be overwritten.');
    }

    public function testPublishWithoutReviewSucceedsWithLivePermission(): void
    {
        // The default TestVoter grants every permission for username "test", so this exercises the
        // happy bypass path. No reviewer has approved, so the publish would normally be blocked.
        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?action=publish&locale=en', $example->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(
            200,
            $response->getStatusCode(),
            \sprintf(
                'Expected 200 for bypass+publish but got %d. Body: %s',
                $response->getStatusCode(),
                (string) $response->getContent(),
            ),
        );

        $finalRequest = $this->workflowTransitionRequestRepository->findOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
        ]);
        $this->assertNotNull($finalRequest, 'Request row should still exist after bypass.');
        // Publishing past an open request closes it, so no stale active request is left behind.
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $this->resolveRequestStatus($finalRequest));
        $this->assertNull($finalRequest->getActiveKey());
    }

    /**
     * An approval delegates the publish right for that request, so the edit permission carries out
     * what the reviewers signed off without ever holding LIVE.
     */
    public function testPublishWithoutLivePermissionSucceedsOnceApproved(): void
    {
        $this->grantTestUserViewAndEditOnly();

        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $request = $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);
        $request->addApproval($this->createRequestCreator('reviewer_one'), WorkflowTransitionRequestDecisionMessage::text('looks good'));
        static::getEntityManager()->flush();

        $this->renameTestUserTo('publish_no_live');
        $this->client->setServerParameter('PHP_AUTH_USER', 'publish_no_live');
        $this->client->setServerParameter('PHP_AUTH_PW', 'test');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?action=publish&locale=en', $example->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(
            200,
            $response->getStatusCode(),
            \sprintf(
                'Expected 200 for an approved publish without LIVE but got %d. Body: %s',
                $response->getStatusCode(),
                (string) $response->getContent(),
            ),
        );

        $publishedRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $request->getId()]);
        $this->assertSame(WorkflowTransitionRequestStatusEnum::PUBLISHED, $this->resolveRequestStatus($publishedRequest));
    }

    public function testPublishWithoutLivePermissionReturns403WhilePending(): void
    {
        $this->grantTestUserViewAndEditOnly();

        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->renameTestUserTo('publish_pending_no_live');
        $this->client->setServerParameter('PHP_AUTH_USER', 'publish_pending_no_live');
        $this->client->setServerParameter('PHP_AUTH_PW', 'test');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?action=publish&locale=en', $example->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(
            403,
            $response->getStatusCode(),
            \sprintf(
                'Expected 403 for a pending publish without LIVE but got %d. Body: %s',
                $response->getStatusCode(),
                (string) $response->getContent(),
            ),
        );
    }

    public function testPublishWithoutReviewReturns403WithoutLivePermission(): void
    {
        $this->grantTestUserViewAndEditOnly();

        $example = $this->createExampleAtDraft();
        $dimensionAttributes = ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'];

        $this->contentManager->applyTransition(
            $example,
            $dimensionAttributes,
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        $this->renameTestUserTo('bypass_no_live');
        $this->client->setServerParameter('PHP_AUTH_USER', 'bypass_no_live');
        $this->client->setServerParameter('PHP_AUTH_PW', 'test');

        $this->client->request(
            'POST',
            \sprintf('/admin/api/examples/%d?action=publish&locale=en', $example->getId()),
        );

        $response = $this->client->getResponse();
        $this->assertSame(
            403,
            $response->getStatusCode(),
            \sprintf(
                'Expected 403 for bypass without LIVE permission but got %d. Body: %s',
                $response->getStatusCode(),
                (string) $response->getContent(),
            ),
        );
    }

    private function findLatestVersion(Example $example): int
    {
        $this->client->request(
            'GET',
            \sprintf('/admin/api/examples/%d/versions?page=1&locale=en&fields=title,version,changer,id', $example->getId()),
        );

        /** @var array{_embedded: array{examples_versions: array<array{version: int}>}} $content */
        $content = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $version = $content['_embedded']['examples_versions'][0]['version'] ?? null;
        $this->assertNotNull($version, 'Publishing should have written a version.');

        return $version;
    }
}
