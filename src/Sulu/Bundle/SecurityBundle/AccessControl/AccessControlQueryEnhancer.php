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
     * Following function uses an own query to load the restricted (not accessible ids). This is faster as embedding the
     * query as subquery. Also loading the "accessible" ids would be more performance intense because sulu has more
     * "accessible" entities that not accessible. Optimized embedded queries are mostly not compatible for MySQL 5.7
     * because of restrictions.
     *
     * As long as we dont have thousands of not "accessible" ids this approach should be faster.
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
        $subQueryBuilder = $this->entityManager->createQueryBuilder()
            ->from($entityClass, 'entity')
            ->select('entity.id');

        $accessClassCondition = 'accessControl.entityClass = :entityClass';
        if ($entityClassField) {
            $accessClassCondition = 'accessControl.entityClass = entity.' . $entityClassField;
        } else {
            $subQueryBuilder->setParameter('entityClass', $entityClass);
        }

        $subQueryBuilder->innerJoin(
            AccessControl::class,
            'accessControl',
            'WITH',
            $accessClassCondition . ' AND ' . $this->getEntityIdCondition($entityClass, 'entity', $entityIdField)
        );
        $subQueryBuilder->innerJoin('accessControl.role', 'role');
        $subQueryBuilder->andWhere(
            'BIT_AND(accessControl.permissions, :permission) <> :permission AND accessControl.permissions IS NOT NULL'
        );

        $subQueryBuilder->andWhere('role.id IN(:roleIds)');

        $subQueryBuilder->setParameter('roleIds', $this->getUserRoleIds($user));
        $subQueryBuilder->setParameter('permission', $permission);

        $result = $subQueryBuilder->getQuery()->getScalarResult();
        $ids = \array_column($result, 'id');

        if (\count($ids) > 0) {
            $queryBuilder->andWhere(\sprintf('%s.id NOT IN (:accessControlIds)', $entityAlias));
            $queryBuilder->setParameter('accessControlIds', $ids);
        }
    }

    /**
     * @param class-string $entityClass
     */
    private function getEntityIdCondition(string $entityClass, string $entityAlias, string $entityIdField = 'id'): string
    {
        $entityIdCondition = 'accessControl.entityId = ' . $entityAlias . '.' . $entityIdField;
        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            if ('integer' === $metadata->getTypeOfField($entityIdField)) {
                $entityIdCondition = 'accessControl.entityIdInteger = ' . $entityAlias . '.' . $entityIdField;
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
