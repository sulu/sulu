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

namespace Sulu\Snippet\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Admin\SnippetAdmin;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class SnippetControllerPermissionsTest extends SuluTestCase
{
    use CreateSnippetTrait;

    public function testPostActionWithPublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('addedituser', 112); // VIEW, ADD and EDIT

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('addedituser');
        $limitedClient->request(
            'POST',
            '/admin/api/snippets?locale=en&action=publish',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'snippet',
                'title' => 'Snippet Without Live Permission',
            ]),
        );

        $this->assertHttpStatusCode(403, $limitedClient->getResponse());
    }

    public function testPostActionWithAddPermissionCreatesDraft(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('addedituser', 112); // VIEW, ADD and EDIT

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('addedituser');
        $limitedClient->request(
            'POST',
            '/admin/api/snippets?locale=en',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'snippet',
                'title' => 'Snippet With Add Permission',
            ]),
        );

        $this->assertHttpStatusCode(201, $limitedClient->getResponse());
    }

    public function testPutActionWithPublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('viewedituser', 80); // VIEW and EDIT

        self::ensureKernelShutdown();

        $snippet = $this->createSnippet([
            'en' => [
                'draft' => [
                    'template' => 'snippet',
                    'title' => 'Snippet',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('viewedituser');
        $limitedClient->request(
            'PUT',
            \sprintf('/admin/api/snippets/%s?locale=en&action=publish', $snippet->getUuid()),
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'snippet',
                'title' => 'Snippet',
            ]),
        );

        $this->assertHttpStatusCode(403, $limitedClient->getResponse());
    }

    /**
     * An approval delegates the publish right of that request, so the edit permission is enough.
     */
    public function testPostTriggerActionPublishWithoutLivePermissionIsAllowedOnceApproved(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('viewedituser', 80); // VIEW and EDIT

        self::ensureKernelShutdown();

        $snippet = $this->createSnippet([
            'en' => [
                'draft' => [
                    'template' => 'review',
                    'title' => 'Snippet',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('viewedituser');
        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?locale=en&action=request_for_review', $snippet->getUuid()),
        );
        $this->assertHttpStatusCode(200, $limitedClient->getResponse());

        $request = self::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class)->getOneBy([
            'resourceKey' => SnippetInterface::RESOURCE_KEY,
            'resourceId' => $snippet->getUuid(),
            'locale' => 'en',
            'active' => true,
        ]);
        $request->addApproval($this->createReviewer(), WorkflowTransitionRequestDecisionMessage::text('looks good'));
        self::getEntityManager()->flush();

        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?locale=en&action=publish', $snippet->getUuid()),
        );

        $this->assertHttpStatusCode(200, $limitedClient->getResponse());
        /** @var array{workflowPlace: string} $content */
        $content = \json_decode((string) $limitedClient->getResponse()->getContent(), true);
        $this->assertSame('published', $content['workflowPlace']);
    }

    public function testPostTriggerActionUnpublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('viewedituser', 80); // VIEW and EDIT

        self::ensureKernelShutdown();

        $snippet = $this->createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Published Snippet',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('viewedituser');
        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?locale=en&action=unpublish', $snippet->getUuid()),
        );

        $this->assertHttpStatusCode(403, $limitedClient->getResponse());
    }

    public function testPostActionWithPublishWithLivePermissionIsAllowed(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('liveuser', 114); // VIEW, ADD, EDIT and LIVE

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('liveuser');
        $limitedClient->request(
            'POST',
            '/admin/api/snippets?locale=en&action=publish',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'snippet',
                'title' => 'Snippet With Live Permission',
            ]),
        );

        $this->assertHttpStatusCode(201, $limitedClient->getResponse());
    }

    public function testPostTriggerActionCopyWithoutAddPermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('viewedituser', 80); // VIEW and EDIT

        self::ensureKernelShutdown();

        $snippet = $this->createSnippet([
            'en' => [
                'draft' => [
                    'template' => 'snippet',
                    'title' => 'Copied Snippet',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('viewedituser');
        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?locale=en&action=copy', $snippet->getUuid()),
        );

        $this->assertHttpStatusCode(403, $limitedClient->getResponse());
    }

    public function testPostTriggerActionCopyWithAddPermissionIsAllowed(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('addedituser', 112); // VIEW, ADD and EDIT

        self::ensureKernelShutdown();

        $snippet = $this->createSnippet([
            'en' => [
                'draft' => [
                    'template' => 'snippet',
                    'title' => 'Copied Snippet',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('addedituser');
        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/snippets/%s?locale=en&action=copy', $snippet->getUuid()),
        );

        $this->assertHttpStatusCode(200, $limitedClient->getResponse());
    }

    private function createUserWithPermissions(string $username, int $permissions): void
    {
        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Limited Role');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions($permissions);
        $permission->setContext(SnippetAdmin::SECURITY_CONTEXT);
        $entityManager->persist($permission);

        $contact = new Contact();
        $contact->setFirstName('Limited');
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername($username);
        $user->setSalt('');
        $user->setLocale('en');
        $user->setEmail($username . '@test.com');
        $user->setContact($contact);

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $hasher = $passwordHasherFactory->getPasswordHasher($user);
        $user->setPassword($hasher->hash('test'));

        $entityManager->persist($user);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale((string) \json_encode(['en']));
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();
        $entityManager->clear();
    }

    private function createReviewer(): User
    {
        $entityManager = self::getEntityManager();

        $contact = new Contact();
        $contact->setFirstName('Reviewer');
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername('reviewer');
        $user->setPassword('test');
        $user->setSalt('');
        $user->setLocale('en');
        $user->setContact($contact);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function createClientForUser(string $username): KernelBrowser
    {
        return $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => 'test',
            ],
        );
    }
}
