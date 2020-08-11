<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\AccessControl;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class AccessControlQueryEnhancer
{
    /**
     * @var SystemStoreInterface
     */
    private $systemStore;

    public function __construct(SystemStoreInterface $systemStore)
    {
        $this->systemStore = $systemStore;
    }

    public function enhance(
        QueryBuilder $queryBuilder,
        UserInterface $user = null,
        int $permission,
        string $entityClass,
        string $entityAlias
    ) {
        $queryBuilder->leftJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND accessControl.entityId = ' . $entityAlias . '.id'
        );
        $queryBuilder->leftJoin('accessControl.role', 'role', 'WITH', 'role.system = :system');
        $queryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) = :permission OR accessControl.permissions IS NULL'
        );

        $roleIds = [];
        if ($user) {
            $roleIds = \array_map(function(RoleInterface $role) {
                return $role->getId();
            }, $user->getRoleObjects());
        } else {
            $anonymousRole = $this->systemStore->getAnonymousRole();
            $roleIds = $anonymousRole ? [$anonymousRole->getId()] : [];
        }

        $queryBuilder->andWhere('role.id IN(:roleIds) OR role.id IS NULL');
        $queryBuilder->setParameter('roleIds', $roleIds);
        $queryBuilder->setParameter('entityClass', $entityClass);
        $queryBuilder->setParameter('permission', $permission);
        $queryBuilder->setParameter('system', $this->systemStore->getSystem());
    }
}
