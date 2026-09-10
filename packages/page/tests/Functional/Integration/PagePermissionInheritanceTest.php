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
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class PagePermissionInheritanceTest extends SuluTestCase
{
    use CreatePageTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );
    }

    public function testGetPageReturnsHasSubFalseForLeafPage(): void
    {
        self::purgeDatabase();

        $homepage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                ],
            ],
        ]);

        $leafPage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Leaf Page',
                    'url' => '/leaf',
                    'parentId' => $homepage->getUuid(),
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $this->client->request('GET', '/admin/api/pages/' . $leafPage->getUuid() . '?locale=en');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertArrayHasKey('hasSub', $content);
        $this->assertFalse($content['hasSub']);
    }

    public function testGetPageReturnsHasSubTrueForPageWithChildren(): void
    {
        self::purgeDatabase();

        $homepage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                ],
            ],
        ]);

        $parentPage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Parent Page',
                    'url' => '/parent',
                    'parentId' => $homepage->getUuid(),
                ],
            ],
        ]);

        $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Child Page',
                    'url' => '/parent/child',
                    'parentId' => $parentPage->getUuid(),
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $this->client->request('GET', '/admin/api/pages/' . $parentPage->getUuid() . '?locale=en');
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        /** @var array<string, mixed> $content */
        $content = \json_decode((string) $response->getContent(), true);

        $this->assertArrayHasKey('hasSub', $content);
        $this->assertTrue($content['hasSub']);
    }

    public function testPermissionInheritanceSetsPermissionsOnChildPages(): void
    {
        self::purgeDatabase();

        $entityManager = self::getEntityManager();

        $role = new Role();
        $role->setName('Child Inheritance Role');
        $role->setSystem('Sulu');

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(127);
        $permission->setContext('sulu.webspaces.sulu-io');
        $role->addPermission($permission);

        $entityManager->persist($role);
        $entityManager->persist($permission);
        $entityManager->flush();

        $roleId = $role->getId();

        $homepage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                ],
            ],
        ]);

        $parentPage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Parent Page',
                    'url' => '/parent',
                    'parentId' => $homepage->getUuid(),
                ],
            ],
        ]);

        /** @var AccessControlManagerInterface $accessControlManager */
        $accessControlManager = self::getContainer()->get('sulu_security.access_control_manager');
        $expectedPermissions = [
            $roleId => [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
                'archive' => false,
                'live' => true,
                'review' => false,
                'security' => true,
            ],
        ];
        $accessControlManager->setPermissions(Page::class, $parentPage->getUuid(), $expectedPermissions);

        $childPage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Child Page',
                    'url' => '/parent/child',
                    'parentId' => $parentPage->getUuid(),
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        /** @var AccessControlManagerInterface $accessControlManager */
        $accessControlManager = self::getContainer()->get('sulu_security.access_control_manager');
        $childPermissions = $accessControlManager->getPermissions(Page::class, $childPage->getUuid());

        // Must not throw AccessControlDescendantProviderNotFoundException for Page type
        $accessControlManager->setPermissions(
            Page::class,
            $parentPage->getUuid(),
            [$roleId => ['view' => true, 'add' => true, 'edit' => true, 'delete' => true, 'live' => true, 'security' => true]],
            true,
        );

        $childPermissions = $accessControlManager->getPermissions(Page::class, $parentPage->getUuid());
        $this->assertArrayHasKey($roleId, $childPermissions);
        $this->assertSame($expectedPermissions, $childPermissions);
    }
}
