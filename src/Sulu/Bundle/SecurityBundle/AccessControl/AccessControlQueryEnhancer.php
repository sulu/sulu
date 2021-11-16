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
use Doctrine\Persistence\Mapping\MappingException;
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
            $this->getEntityIdCondition($entityClass, $entityAlias)
        );

        $queryBuilder->setParameter('entityClass', $entityClass);
    }

    public function enhanceCount(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias
    ): void {
        $this->enhanceCountQueryWithAccessControl(
            $queryBuilder,
            $user,
            $permission,
            $this->getEntityIdCondition($entityClass, $entityAlias),
            $entityClass,
            $entityAlias
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
            'accessControl.entityId = ' . $entityAlias . '.' . $entityIdField
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

        $queryBuilder->andWhere('role.id IN(:roleIds) OR role.id IS NULL');
        $queryBuilder->setParameter('roleIds', $this->getUserRoleIds($user));
        $queryBuilder->setParameter('permission', $permission);
        $queryBuilder->setParameter('system', $this->systemStore->getSystem());
    }

    private function enhanceCountQueryWithAccessControl(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityIdCondition,
        string $entityClass,
        string $entityAlias
    ): void {
        $subQueryBuilder = $this->entityManager->createQueryBuilder()
            ->from($entityClass, 'entity')
            ->select('entity.id');

        $subQueryBuilder->leftJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            'accessControl.entityClass = :entityClass AND ' . $entityIdCondition
        );
        $subQueryBuilder->leftJoin('accessControl.role', 'role', 'WITH', 'role.system = :system');
        $subQueryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) = :permission OR accessControl.permissions IS NULL'
        );

        $subQueryBuilder->andWhere('role.id IN(:roleIds) OR role.id IS NULL');
        $queryBuilder->setParameter('roleIds', $this->getUserRoleIds($user));
        $queryBuilder->setParameter('permission', $permission);
        $queryBuilder->setParameter('system', $this->systemStore->getSystem());

        $queryBuilder->andWhere(\sprintf('%s.id IN (%s)', $entityAlias, $subQueryBuilder->getDQL()));
    }

    private function getEntityIdCondition(string $entityClass, string $entityAlias): string
    {
        $entityIdCondition = 'accessControl.entityId = ' . $entityAlias . '.id';
        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            if ('integer' === $metadata->getTypeOfField('id')) {
                $entityIdCondition = 'accessControl.entityIdInteger = ' . $entityAlias . '.id';
            }
        } catch (MappingException $e) {
            $metadata = null;
        }

        return $entityIdCondition;
    }

    /**
     * @return int[]
     */
    private function getUserRoleIds(?UserInterface $user): array
    {
        if ($user) {
            return \array_map(function(RoleInterface $role) {
                return $role->getId();
            }, $user->getRoleObjects());
        }

        $anonymousRole = $this->systemStore->getAnonymousRole();

        return $anonymousRole ? [$anonymousRole->getId()] : [];
    }
}
