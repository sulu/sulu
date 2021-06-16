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

use Doctrine\DBAL\Connection;
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
            'CAST(accessControl.entityId AS STRING) = CAST(' . $entityAlias . '.id AS STRING)'
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
            'CAST(accessControl.entityId AS STRING) = CAST(' . $entityAlias . '.' . $entityIdField . ' AS STRING)'
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

    /**
     * @param array<string|int> $entityIds
     *
     * @return array<string|int>
     */
    public function findIdsWithGrantedPermissions(
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        array $entityIds
    ): array {
        $systemRoleQueryBuilder = $this->entityManager->createQueryBuilder()
            ->from(RoleInterface::class, 'systemRoles')
            ->select('systemRoles.id')
            ->where('systemRoles.system = :system');

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('accessControl.entityId as id')
            ->distinct()
            ->from(AccessControl::class, 'accessControl')
            ->innerJoin('accessControl.role', 'role')
            ->where('accessControl.entityClass = :entityClass')
            ->andWhere('CAST(accessControl.entityId AS STRING) IN (:entityIds)')
            ->andWhere('accessControl.role IN (' . $systemRoleQueryBuilder->getDQL() . ')')
            ->setParameter('system', $this->systemStore->getSystem())
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityIds', $entityIds, Connection::PARAM_STR_ARRAY)
        ;

        $idsWithPermissions = \array_column($queryBuilder->getQuery()->getArrayResult(), 'id');
        $idsWithoutPermissions = \array_diff($entityIds, $idsWithPermissions);

        if ($user) {
            $roleIds = \array_map(function(RoleInterface $role) {
                return $role->getId();
            }, $user->getRoleObjects());
        } else {
            $anonymousRole = $this->systemStore->getAnonymousRole();
            $roleIds = $anonymousRole ? [$anonymousRole->getId()] : [];
        }

        $queryBuilder
            ->andWhere('BIT_AND(accessControl.permissions, :permission) = :permission')
            ->andWhere('role.id IN(:roleIds)')
            ->setParameter('roleIds', $roleIds)
            ->setParameter('permission', $permission)
        ;

        $idsWithGrantedPermissions = \array_column($queryBuilder->getQuery()->getArrayResult(), 'id');

        return \array_merge($idsWithGrantedPermissions, $idsWithoutPermissions);
    }
}
