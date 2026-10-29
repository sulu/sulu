<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\NoResultException;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;

/**
 * @extends EntityRepository<AccessControlInterface>
 */
class AccessControlRepository extends EntityRepository implements AccessControlRepositoryInterface
{
    public function findByTypeAndIdAndRole($type, $id, $roleId)
    {
        try {
            $queryBuilder = $this->createQueryBuilder('accessControl')
                ->leftJoin('accessControl.role', 'role')
                ->where('accessControl.entityId = :entityId')
                ->andWhere('accessControl.entityClass = :entityClass')
                ->andWhere('role.id = :roleId');

            $query = $queryBuilder->getQuery()
                ->setParameter('entityId', $this->normalizeEntityId($type, $id))
                ->setParameter('entityClass', $type)
                ->setParameter('roleId', $roleId);

            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    public function findByTypeAndId($type, $id, $system = null)
    {
        $queryBuilder = $this->createQueryBuilder('accessControl')
            ->leftJoin('accessControl.role', 'role')
            ->where('accessControl.entityId = :entityId')
            ->andWhere('accessControl.entityClass = :entityClass');

        if ($system) {
            $queryBuilder->andWhere('role.system = :system');
            $queryBuilder->setParameter('system', $system);
        }

        $queryBuilder
            ->setParameter('entityId', $this->normalizeEntityId($type, $id))
            ->setParameter('entityClass', $type);

        /** @var AccessControlInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * The database loads an entity with an integer id by the numeric value of the given id ("04855" loads 4855), while
     * the permissions are stored with the canonical id, so a non-canonical id would not find them.
     *
     * @param string|int|null $id
     *
     * @return string|int|null
     */
    private function normalizeEntityId(string $type, $id)
    {
        if (!\is_string($id) || 1 !== \preg_match('/^\s*\+?\d/', $id)) {
            return $id;
        }

        $metadataFactory = $this->getEntityManager()->getMetadataFactory();
        if (!\class_exists($type) || $metadataFactory->isTransient($type)) {
            return $id;
        }

        $metadata = $metadataFactory->getMetadataFor($type);
        $identifierFieldNames = $metadata->getIdentifierFieldNames();
        if (1 !== \count($identifierFieldNames)
            || !\in_array($metadata->getTypeOfField($identifierFieldNames[0]), ['integer', 'smallint', 'bigint'], true)
        ) {
            return $id;
        }

        return (string) (int) $id;
    }

    public function findIdsWithGrantedPermissions(
        ?UserInterface $user,
        int $permission,
        string $entityClass,
        array $entityIds,
        ?string $system,
        ?int $anonymousRoleId
    ): array {
        $systemRoleQueryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->from(RoleInterface::class, 'systemRoles')
            ->select('systemRoles.id')
            ->where('systemRoles.system = :system');

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('accessControl.entityId as id')
            ->distinct()
            ->from(AccessControl::class, 'accessControl')
            ->innerJoin('accessControl.role', 'role')
            ->where('accessControl.entityClass = :entityClass')
            ->andWhere('CAST(accessControl.entityId AS STRING) IN (:entityIds)')
            ->andWhere('accessControl.role IN (' . $systemRoleQueryBuilder->getDQL() . ')')
            ->setParameter('system', $system)
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityIds', $entityIds, ArrayParameterType::STRING)
        ;

        /** @var string[] $idsWithPermissions */
        $idsWithPermissions = \array_column($queryBuilder->getQuery()->getArrayResult(), 'id');
        $idsWithoutPermissions = \array_diff($entityIds, $idsWithPermissions);

        if ($user) {
            $roleIds = \array_map(function(RoleInterface $role) {
                return $role->getId();
            }, $user->getRoleObjects());
        } else {
            $roleIds = $anonymousRoleId ? [$anonymousRoleId] : [];
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
