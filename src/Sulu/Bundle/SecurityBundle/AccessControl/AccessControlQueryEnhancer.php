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
use Sulu\Component\Security\Authentication\UserInterface;

class AccessControlQueryEnhancer implements AccessControlQueryEnhancerInterface
{
    public function __construct(
        private SystemStoreInterface $systemStore,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param class-string $entityClass
     */
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
            $entityClass,
            $entityAlias
        );
    }

    /**
     * @param class-string $entityClass
     */
    public function enhanceWithDynamicEntityClass(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias,
        string $entityClassField,
        string $entityIdField
    ): void {
        $this->enhanceQueryWithAccessControl(
            $queryBuilder,
            $user,
            $permission,
            $entityClass,
            $entityAlias,
            $entityIdField,
            $entityClassField
        );
    }

    /**
     * Following function uses an EXISTS subquery to find entities where at least one of the user's roles
     * has the required permission. This fixes the issue where users with multiple roles would lose access
     * if any role lacked permission, instead of maintaining access if any role had permission.
     *
     * The EXISTS approach ensures correct permission logic while maintaining good performance.
     *
     * @param class-string $entityClass
     */
    private function enhanceQueryWithAccessControl(
        QueryBuilder $queryBuilder,
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        string $entityAlias,
        string $entityIdField = 'id',
        ?string $entityClassField = null
    ): void {
        $roleIds = $this->getUserRoleIds($user);

        if (empty($roleIds)) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $existsSubQuery = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(AccessControl::class, 'acl')
            ->innerJoin('acl.role', 'role')
            ->where('role.id IN (:roleIds)')
            ->andWhere('BIT_AND(acl.permissions, :permission) = :permission')
            ->andWhere('acl.permissions IS NOT NULL');

        if ($entityClassField) {
            $existsSubQuery->andWhere('acl.entityClass = ' . $entityAlias . '.' . $entityClassField);
        } else {
            $existsSubQuery->andWhere('acl.entityClass = :entityClass');
        }

        $entityIdCondition = $this->getEntityIdCondition($entityClass, $entityAlias, $entityIdField);
        $existsSubQuery->andWhere($entityIdCondition);

        // Create a subquery to check if ANY AccessControl records exist for this entity
        // This allows unrestricted entities (without any permission records) to be visible
        $noRestrictionsSubQuery = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(AccessControl::class, 'acl_check')
            ->where($this->getEntityIdConditionWithAlias($entityClass, $entityAlias, $entityIdField, 'acl_check'));

        if ($entityClassField) {
            $noRestrictionsSubQuery->andWhere('acl_check.entityClass = ' . $entityAlias . '.' . $entityClassField);
        } else {
            $noRestrictionsSubQuery->andWhere('acl_check.entityClass = :entityClass');
        }

        // Set parameters before adding WHERE clause
        $queryBuilder->setParameter('roleIds', $roleIds);
        $queryBuilder->setParameter('permission', $permission);

        if (!$entityClassField) {
            $queryBuilder->setParameter('entityClass', $entityClass);
        }

        // Show entities if:
        // 1. No AccessControl records exist (unrestricted), OR
        // 2. Dynamic entity class field is NULL (no permission check needed), OR
        // 3. User has permission via at least one of their roles
        if ($entityClassField) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $entityAlias . '.' . $entityClassField . ' IS NULL',
                    $queryBuilder->expr()->not($queryBuilder->expr()->exists($noRestrictionsSubQuery->getDQL())),
                    $queryBuilder->expr()->exists($existsSubQuery->getDQL())
                )
            );
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->not($queryBuilder->expr()->exists($noRestrictionsSubQuery->getDQL())),
                    $queryBuilder->expr()->exists($existsSubQuery->getDQL())
                )
            );
        }
    }

    /**
     * @param class-string $entityClass
     */
    private function getEntityIdCondition(string $entityClass, string $entityAlias, string $entityIdField = 'id'): string
    {
        return $this->getEntityIdConditionWithAlias($entityClass, $entityAlias, $entityIdField, 'acl');
    }

    /**
     * @param class-string $entityClass
     */
    private function getEntityIdConditionWithAlias(string $entityClass, string $entityAlias, string $entityIdField, string $aclAlias): string
    {
        $entityIdCondition = $aclAlias . '.entityId = ' . $entityAlias . '.' . $entityIdField;
        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            if ('integer' === $metadata->getTypeOfField($entityIdField)) {
                $entityIdCondition = $aclAlias . '.entityIdInteger = ' . $entityAlias . '.' . $entityIdField;
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
            $roleIds = [];

            foreach ($user->getRoleObjects() as $role) {
                if ($role->getSystem() === $this->systemStore->getSystem()) {
                    $roleIds[] = $role->getId();
                }
            }

            return $roleIds;
        }

        $anonymousRole = $this->systemStore->getAnonymousRole();

        return $anonymousRole ? [$anonymousRole->getId()] : [];
    }
}
