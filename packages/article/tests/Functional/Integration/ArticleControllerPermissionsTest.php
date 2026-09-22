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

namespace Sulu\Article\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class ArticleControllerPermissionsTest extends SuluTestCase
{
    use CreateArticleTrait;

    public function testPostActionWithPublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('addedituser', 112); // VIEW, ADD and EDIT

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('addedituser');
        $limitedClient->request(
            'POST',
            '/admin/api/articles?locale=en&action=publish',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'article',
                'title' => 'Article Without Live Permission',
                'url' => '/article-without-live-permission',
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
            '/admin/api/articles?locale=en',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'article',
                'title' => 'Article With Add Permission',
                'url' => '/article-with-add-permission',
            ]),
        );

        $this->assertHttpStatusCode(201, $limitedClient->getResponse());
    }

    public function testPutActionWithPublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('viewedituser', 80); // VIEW and EDIT

        self::ensureKernelShutdown();

        $article = $this->createArticle([
            'en' => [
                'draft' => [
                    'template' => 'article',
                    'title' => 'Article',
                    'url' => '/article',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('viewedituser');
        $limitedClient->request(
            'PUT',
            \sprintf('/admin/api/articles/%s?locale=en&action=publish', $article->getUuid()),
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'article',
                'title' => 'Article',
                'url' => '/article',
            ]),
        );

        $this->assertHttpStatusCode(403, $limitedClient->getResponse());
    }

    public function testPostTriggerActionUnpublishWithoutLivePermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('viewedituser', 80); // VIEW and EDIT

        self::ensureKernelShutdown();

        $article = $this->createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Published Article',
                    'url' => '/published-article',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('viewedituser');
        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/articles/%s?locale=en&action=unpublish', $article->getUuid()),
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
            '/admin/api/articles?locale=en&action=publish',
            [],
            [],
            [],
            (string) \json_encode([
                'template' => 'article',
                'title' => 'Article With Live Permission',
                'url' => '/article-with-live-permission',
            ]),
        );

        $this->assertHttpStatusCode(201, $limitedClient->getResponse());
    }

    public function testPostTriggerActionCopyWithoutAddPermissionIsForbidden(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('viewedituser', 80); // VIEW and EDIT

        self::ensureKernelShutdown();

        $article = $this->createArticle([
            'en' => [
                'draft' => [
                    'template' => 'article',
                    'title' => 'Copied Article',
                    'url' => '/copied-article',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('viewedituser');
        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/articles/%s?locale=en&action=copy', $article->getUuid()),
        );

        $this->assertHttpStatusCode(403, $limitedClient->getResponse());
    }

    public function testPostTriggerActionCopyWithAddPermissionIsAllowed(): void
    {
        self::purgeDatabase();

        $this->createUserWithPermissions('addedituser', 112); // VIEW, ADD and EDIT

        self::ensureKernelShutdown();

        $article = $this->createArticle([
            'en' => [
                'draft' => [
                    'template' => 'article',
                    'title' => 'Copied Article',
                    'url' => '/copied-article',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $limitedClient = $this->createClientForUser('addedituser');
        $limitedClient->request(
            'POST',
            \sprintf('/admin/api/articles/%s?locale=en&action=copy', $article->getUuid()),
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
        $permission->setContext(ArticleAdmin::SECURITY_CONTEXT);
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
