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
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class PageControllerPermissionsTest extends SuluTestCase
{
    use AssertSnapshotTrait;
    use CreatePageTrait;
    use CreatePageWithPermissionsTrait;

    /**
     * @var KernelBrowser
     */
    protected $client;

    public function testCgetActionWithoutWebspacePermissionsReturnsEmpty(): void
    {
        self::purgeDatabase();

        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Limited Role');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(127);
        $permission->setContext('sulu.webspaces.sulu-io');
        $entityManager->persist($permission);

        $contact = new Contact();
        $contact->setFirstName('Limited');
        $contact->setLastName('User');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername('limiteduser');
        $user->setSalt('');
        $user->setLocale('en');
        $user->setEmail('limiteduser@test.com');
        $user->setContact($contact);

        $passwordHasherFactory = self::getContainer()->get('security.password_hasher_factory');
        $hasher = $passwordHasherFactory->getPasswordHasher($user);
        $user->setPassword($hasher->hash('test'));

        $entityManager->persist($user);

        // Link user to role
        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale((string) \json_encode(['en']));
        $user->addUserRole($userRole);
        $entityManager->persist($userRole);

        $entityManager->flush();
        $entityManager->clear();

        self::ensureKernelShutdown();

        $homepage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                ],
            ],
        ]);
        $this->createPage([
            'en' => [
                'live' => [
                    'parentId' => $homepage->getUuid(),
                    'title' => 'Sulu-io Page',
                    'template' => 'default',
                    'url' => '/sulu-io-test-no-permission',
                ],
            ],
        ]);
        $this->createPage([
            'en' => [
                'live' => [
                    'parentId' => $homepage->getUuid(),
                    'title' => 'Sulu-io Page 2',
                    'template' => 'default',
                    'url' => '/sulu-io-test-no-permission2',
                ],
            ],
        ]);
        $homepage2 = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage 2',
                    'url' => '/',
                ],
            ],
        ], 'blog');
        $this->createPage([
            'en' => [
                'live' => [
                    'parentId' => $homepage2->getUuid(),
                    'title' => 'Blog Page',
                    'template' => 'default',
                    'url' => '/blog-test-no-permission',
                ],
            ],
        ], 'blog');

        self::ensureKernelShutdown();

        $limitedClient = $this->createAuthenticatedClient(
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'PHP_AUTH_USER' => 'limiteduser',
                'PHP_AUTH_PW' => 'test',
            ],
        );

        $limitedClient->request(
            'GET',
            '/admin/api/pages?locale=en',
        );

        $response = $limitedClient->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded?: array{pages?: list<array<string, mixed>>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pagesSuluIo = $content['_embedded']['pages'] ?? [];

        $this->assertNotEmpty($pagesSuluIo, 'User with sulu-io permission should see sulu-io pages');
        $this->assertCount(1, $pagesSuluIo);

        $limitedClient->request(
            'GET',
            '/admin/api/pages?locale=en&webspace=blog',
        );

        $response = $limitedClient->getResponse();
        $this->assertHttpStatusCode(200, $response);

        /** @var array{_embedded?: array{pages?: list<array<string, mixed>>}} $content */
        $content = \json_decode((string) $response->getContent(), true);
        $pagesBlog = $content['_embedded']['pages'] ?? [];

        $this->assertEmpty($pagesBlog, 'User without blog permission should not see blog pages');
    }
}
