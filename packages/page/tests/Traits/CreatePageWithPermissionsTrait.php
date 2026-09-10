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

namespace Sulu\Page\Tests\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;

trait CreatePageWithPermissionsTrait
{
    abstract protected static function getEntityManager(): EntityManagerInterface;

    protected function createAnonymousRoleWithWebspacePermissions(string $webspaceKey = 'sulu-test-secure'): Role
    {
        $role = new Role();
        $role->setName('Anonymous User Website');
        $role->setSystem($webspaceKey);
        $role->setAnonymous(true);

        $permission = new Permission();
        $permission->setRole($role);
        $permission->setPermissions(255); // All permissions at webspace level
        $permission->setContext(\sprintf('sulu.webspaces.%s', $webspaceKey));
        $role->addPermission($permission);

        self::getEntityManager()->persist($role);
        self::getEntityManager()->persist($permission);
        self::getEntityManager()->flush();

        return $role;
    }

    protected function setPagePermissions(PageInterface $page, Role $role, int $permissions): void
    {
        $accessControl = new AccessControl();
        $accessControl->setPermissions($permissions);
        $accessControl->setEntityId($page->getUuid());
        $accessControl->setEntityClass(Page::class);
        $accessControl->setRole($role);

        self::getEntityManager()->persist($accessControl);
        self::getEntityManager()->flush();
    }

    protected function denyAccessToPage(PageInterface $page, Role $role): void
    {
        $this->setPagePermissions($page, $role, 0);
    }

    protected function grantViewAccessToPage(PageInterface $page, Role $role): void
    {
        $this->setPagePermissions($page, $role, 64); // VIEW permission
    }

    protected function grantAllAccessToPage(PageInterface $page, Role $role): void
    {
        $this->setPagePermissions($page, $role, 255); // All permissions
    }
}
