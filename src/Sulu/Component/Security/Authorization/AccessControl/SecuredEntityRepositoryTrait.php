<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Component\Security\Authentication\UserInterface;

trait SecuredEntityRepositoryTrait
{
    protected function addAccessControl(QueryBuilder $queryBuilder, UserInterface $user, $entityClass, $entityAlias)
    {
        $queryBuilder->leftJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND accessControl.entityId = ' . $entityAlias . '.id'
        );
        $queryBuilder->leftJoin('accessControl.role', 'role');
        // TODO remove hard coded permission value
        $queryBuilder->andWhere('BIT_AND(accessControl.permissions, 64) = 64 OR accessControl.permissions IS NULL');

        $roleIds = [];
        foreach ($user->getRoleObjects() as $role) {
            $roleIds[] = $role->getId();
        }

        $queryBuilder->andWhere('role.id IN(:roleIds) OR role.id IS NULL');
        $queryBuilder->setParameter('roleIds', $roleIds);
        $queryBuilder->setParameter('entityClass', $entityClass);
    }
}
