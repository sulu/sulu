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
use Sulu\Component\Security\Authentication\UserInterface;

class AccessControlQueryEnhancer
{
    public function enhance(
        QueryBuilder $queryBuilder,
        UserInterface $user,
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
        $queryBuilder->leftJoin('accessControl.role', 'role');
        $queryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) = :permission OR accessControl.permissions IS NULL'
        );

        // TODO use anonymous user
        $roleIds = [];
        foreach ($user->getRoleObjects() as $role) {
            $roleIds[] = $role->getId();
        }

        $queryBuilder->andWhere('role.id IN(:roleIds) OR role.id IS NULL');
        $queryBuilder->setParameter('roleIds', $roleIds);
        $queryBuilder->setParameter('entityClass', $entityClass);
        $queryBuilder->setParameter('permission', $permission);
    }
}
