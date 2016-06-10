<?php

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\NoResultException;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;

class InheritedAccessControlRepository extends EntityRepository implements AccessControlRepositoryInterface {
    /**
     * @inheritdoc
     */
    public function findByTypeAndIdAndRole($type, $id, $roleId) {
        try {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder->select('a')
                ->from($type, 'c')
                ->join($type, 'p', 'WITH', $queryBuilder->expr()->between('c.lft', 'p.lft', 'p.rgt'))
                ->join('Sulu\Bundle\SecurityBundle\Entity\AccessControl', 'a', 'WITH', $queryBuilder->expr()->eq('p.id', 'a.entityId'))
                ->leftJoin('a.role', 'role')
                ->where('c.id = :entityId')
                ->andWhere('a.entityClass = :entityClass')
                ->andWhere('role.id = :roleId')
                ->orderBy('p.lft', 'DESC')
                ->setMaxResults(1);

            $query = $queryBuilder->getQuery()
                ->setParameter('entityId', $id)
                ->setParameter('entityClass', $type)
                ->setParameter('roleId', $roleId);

            return $query->getSingleResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    /**
     * @inheritdoc
     */
    public function findByTypeAndId($type, $id) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('a')
            ->from($type, 'c')
            ->join($type, 'p', 'WITH', $queryBuilder->expr()->between('c.lft', 'p.lft', 'p.rgt'))
            ->join('Sulu\Bundle\SecurityBundle\Entity\AccessControl', 'a', 'WITH', $queryBuilder->expr()->eq('p.id', 'a.entityId'))
            ->where('c.id = :entityId')
            ->andWhere('a.entityClass = :entityClass')
            ->orderBy('p.lft', 'DESC')
            ->setMaxResults(1);

        $query = $queryBuilder->getQuery()
            ->setParameter('entityId', $id)
            ->setParameter('entityClass', $type);

        return $query->getResult();
    }
}