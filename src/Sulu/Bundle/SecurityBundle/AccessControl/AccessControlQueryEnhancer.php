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

use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(SystemStoreInterface $systemStore, EntityManagerInterface $entityManager)
    {
        $this->systemStore = $systemStore;
        $this->entityManager = $entityManager;
    }

    public function enhance(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias
    ): void {
        $this->enhanceQueryWithAccessControl(
            $queryBuilder,
            $user,
            $permission,
            'accessControl.entityClass = :entityClass',
            'accessControl.entityId = CAST(' . $entityAlias . '.id AS STRING)'
        );

        $queryBuilder->setParameter('entityClass', $entityClass);
    }

    public function enhanceWithDynamicEntityClass(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClassField,
        string $entityIdField,
        string $entityAlias
    ): void {
        $this->enhanceQueryWithAccessControl(
            $queryBuilder,
            $user,
            $permission,
            'accessControl.entityClass = ' . $entityAlias . '.' . $entityClassField,
            'accessControl.entityId = CAST(' . $entityAlias . '.' . $entityIdField . ' AS STRING)'
        );
    }

    private function enhanceQueryWithAccessControl(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClassCondition,
        string $entityIdCondition
    ): void {
        $systemRoleQueryBuilder = $this->entityManager->createQueryBuilder()
            ->from(RoleInterface::class, 'systemRoles')
            ->select('systemRoles.id')
            ->where('systemRoles.system = :system');

        $queryBuilder->leftJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            $entityClassCondition . ' AND ' . $entityIdCondition . ' '
            . 'AND accessControl.role IN (' . $systemRoleQueryBuilder->getDQL() . ')'
        );
        $queryBuilder->leftJoin('accessControl.role', 'role');
        $queryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) = :permission OR accessControl.permissions IS NULL'
        );

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
        $queryBuilder->setParameter('permission', $permission);
        $queryBuilder->setParameter('system', $this->systemStore->getSystem());
    }
}
