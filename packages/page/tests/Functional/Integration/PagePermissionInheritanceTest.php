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

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[CoversNothing]
class PagePermissionInheritanceTest extends SuluTestCase
{
    use CreatePageTrait;

    public function testChildPageInheritsParentPermissionsOnCreation(): void
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

        $this->assertSame($expectedPermissions, $childPermissions);
    }
}
