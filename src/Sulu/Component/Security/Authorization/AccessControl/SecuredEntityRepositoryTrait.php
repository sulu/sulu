<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * This trait adds functionality to add filtering for access control to doctrine query builders.
 *
 * @deprecated Will be removed with 3.0, use the Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer instead.
 */
trait SecuredEntityRepositoryTrait
{
    /**
     * Adds joins and conditions to the QueryBuilder in order to only return entities the given user is allowed to see.
     *
     * @param QueryBuilder $queryBuilder The instance of the QueryBuilder to adjust
     * @param UserInterface $user The user for which the access control is checked
     * @param int $permission The permission mask for which is checked
     * @param string $entityClass The class of the entity of which the access control is checked
     * @param string $entityAlias The alias of the entity used in the query builder
     */
    protected function addAccessControl(
        QueryBuilder $queryBuilder,
        UserInterface $user,
        $permission,
        $entityClass,
        $entityAlias
    ) {
        @trigger_deprecation(
            'sulu/sulu',
            '2.2',
            'The "%s" is deprecated. Use the "%s" service instead.',
            __TRAIT__,
            AccessControlQueryEnhancer::class
        );

        $queryBuilder->leftJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass '
            . 'AND CAST(accessControl.entityId AS STRING) = CAST(' . $entityAlias . '.id AS STRING)'
        );
        $queryBuilder->leftJoin('accessControl.role', 'role');
        $queryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) = :permission OR accessControl.permissions IS NULL'
        );

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
