<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sulu\Bundle\MediaBundle\Entity\Collection as CollectionEntity;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\DescendantProviderInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityRepositoryTrait;

/**
 * CollectionRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CollectionRepository extends NestedTreeRepository implements CollectionRepositoryInterface, DescendantProviderInterface
{
    use SecuredEntityRepositoryTrait;

    /**
     * @var AccessControlQueryEnhancer
     */
    private $accessControlQueryEnhancer;

    public function findCollectionById($id)
    {
        $dql = \sprintf(
            'SELECT n, collectionMeta, defaultMeta, collectionType, collectionParent, parentMeta, collectionChildren
                 FROM %s AS n
                     LEFT JOIN n.meta AS collectionMeta
                     LEFT JOIN n.defaultMeta AS defaultMeta
                     LEFT JOIN n.type AS collectionType
                     LEFT JOIN n.parent AS collectionParent
                     LEFT JOIN n.children AS collectionChildren
                     LEFT JOIN collectionParent.meta AS parentMeta
                 WHERE n.id = :id',
            $this->_entityName
        );

        $query = new Query($this->_em);
        $query->setDQL($dql);
        $query->setParameter('id', $id);
        $result = $query->getResult();

        if (0 === \count($result)) {
            return;
        }

        return $result[0];
    }

    public function findCollectionSet(
        $depth = 0,
        $filter = [],
        CollectionInterface $collection = null,
        $sortBy = [],
        UserInterface $user = null,
        $permission = null
    ) {
        $ids = $this->getIdsQuery($depth, $filter, $sortBy, $collection)->getScalarResult();

        $queryBuilder = $this->createQueryBuilder('collection')
            ->addSelect('collectionMeta')
            ->addSelect('defaultMeta')
            ->addSelect('collectionType')
            ->addSelect('collectionParent')
            ->addSelect('parentMeta')
            ->leftJoin('collection.meta', 'collectionMeta')
            ->leftJoin('collection.defaultMeta', 'defaultMeta')
            ->leftJoin('collection.type', 'collectionType')
            ->leftJoin('collection.parent', 'collectionParent')
            ->leftJoin('collectionParent.meta', 'parentMeta')
            ->where('collection.id IN (:ids)')
            ->setParameter('ids', $ids);

        if (null !== $sortBy && \is_array($sortBy) && \count($sortBy) > 0) {
            foreach ($sortBy as $column => $order) {
                $queryBuilder->addOrderBy(
                    'collectionMeta.' . $column,
                    ('asc' === \strtolower($order) ? 'ASC' : 'DESC')
                );
            }
        }

        $queryBuilder->addOrderBy('collection.id', 'ASC');

        if (null != $permission) {
            if ($this->accessControlQueryEnhancer) {
                $this->accessControlQueryEnhancer->enhance(
                    $queryBuilder,
                    $user,
                    $permission,
                    Collection::class,
                    'collection'
                );
            } else {
                $this->addAccessControl(
                    $queryBuilder,
                    $user,
                    $permission,
                    Collection::class,
                    'collection'
                );
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function countCollections($depth = 0, $filter = [], CollectionInterface $collection = null)
    {
        $ids = $this->getIdsQuery($depth, $filter, [], $collection, 'DISTINCT collection.id')->getScalarResult();

        try {
            return \count($ids);
        } catch (NoResultException $e) {
            return;
        }
    }

    public function countMedia(CollectionInterface $collection)
    {
        if (!$collection || !$collection->getId()) {
            throw new \InvalidArgumentException();
        }

        $queryBuilder = $this->createQueryBuilder('collection')
            ->select('COUNT(collectionMedia.id)')
            ->leftJoin('collection.media', 'collectionMedia')
            ->where('collection.id = :id')
            ->setParameter('id', $collection->getId());

        return \intval($queryBuilder->getQuery()->getSingleScalarResult());
    }

    public function countSubCollections(CollectionInterface $collection)
    {
        if (!$collection || !$collection->getId()) {
            throw new \InvalidArgumentException();
        }

        $queryBuilder = $this->createQueryBuilder('collection')
            ->select('COUNT(subCollections.id)')
            ->leftJoin('collection.children', 'subCollections')
            ->where('collection.id = :id')
            ->setParameter('id', $collection->getId());

        return \intval($queryBuilder->getQuery()->getSingleScalarResult());
    }

    public function findCollections($filter = [], $limit = null, $offset = null, $sortBy = [])
    {
        list($parent, $depth, $search) = [
            isset($filter['parent']) ? $filter['parent'] : null,
            isset($filter['depth']) ? $filter['depth'] : null,
            isset($filter['search']) ? $filter['search'] : null,
        ];

        try {
            $qb = $this->createQueryBuilder('collection')
                ->leftJoin('collection.meta', 'collectionMeta')
                ->leftJoin('collection.defaultMeta', 'defaultMeta')
                ->leftJoin('collection.type', 'type')
                ->leftJoin('collection.parent', 'parent')
                ->leftJoin('collection.children', 'children')
                ->addSelect('collectionMeta')
                ->addSelect('defaultMeta')
                ->addSelect('type')
                ->addSelect('parent')
                ->addSelect('children');

            if (null !== $sortBy && \is_array($sortBy) && \count($sortBy) > 0) {
                foreach ($sortBy as $column => $order) {
                    $qb->addOrderBy('collectionMeta.' . $column, 'asc' === \strtolower($order) ? 'ASC' : 'DESC');
                }
            }
            $qb->addOrderBy('collection.id', 'ASC');

            if (null !== $parent) {
                $qb->andWhere('parent.id = :parent');
                $qb->setParameter('parent', $parent);
            } elseif (null !== $depth) {
                // the combination of depth and parent needs a bigger refactoring of this query.
                $qb->andWhere('collection.depth <= :depth');
                $qb->setParameter('depth', \intval($depth));
            }
            if (null !== $search) {
                $qb->andWhere('collectionMeta.title LIKE :search');
                $qb->setParameter('search', '%' . $search . '%');
            }
            if (null !== $offset) {
                $qb->setFirstResult($offset);
            }
            if (null !== $limit) {
                $qb->setMaxResults($limit);
            }

            return new Paginator($qb->getQuery());
        } catch (NoResultException $ex) {
            return;
        }
    }

    public function findCollectionBreadcrumbById($id)
    {
        try {
            $sql = \sprintf(
                'SELECT n, collectionMeta, defaultMeta
                 FROM %s AS p,
                      %s AS n
                        LEFT JOIN n.meta AS collectionMeta
                        LEFT JOIN n.defaultMeta AS defaultMeta
                 WHERE p.id = :id AND p.lft > n.lft AND p.rgt < n.rgt
                 ORDER BY n.lft',
                $this->_entityName,
                $this->_entityName
            );

            $query = new Query($this->_em);
            $query->setDQL($sql);
            $query->setParameter('id', $id);

            return $query->getResult();
        } catch (NoResultException $ex) {
            return [];
        }
    }

    public function findCollectionByKey($key)
    {
        $queryBuilder = $this->createQueryBuilder('collection')
            ->leftJoin('collection.meta', 'collectionMeta')
            ->leftJoin('collection.defaultMeta', 'defaultMeta')
            ->where('collection.key = :key');

        $query = $queryBuilder->getQuery();
        $query->setParameter('key', $key);

        try {
            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    public function findTree($id, $locale)
    {
        $subQueryBuilder = $this->createQueryBuilder('subCollection')
            ->select('subCollection.id')
            ->leftJoin($this->_entityName, 'c', Join::WITH, 'c.id = :id')
            ->andWhere('subCollection.lft <= c.lft AND subCollection.rgt > c.lft');

        $queryBuilder = $this->createQueryBuilder('collection')
            ->addSelect('meta')
            ->addSelect('defaultMeta')
            ->addSelect('type')
            ->addSelect('parent')
            ->leftJoin('collection.meta', 'meta', Join::WITH, 'meta.locale = :locale')
            ->leftJoin('collection.defaultMeta', 'defaultMeta')
            ->innerJoin('collection.type', 'type')
            ->leftJoin('collection.parent', 'parent')
            ->where(\sprintf('parent.id IN (%s)', $subQueryBuilder->getDQL()))
            ->orWhere('parent.id is NULL')
            ->orderBy('collection.lft')
            ->setParameter('id', $id)
            ->setParameter('locale', $locale);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findCollectionTypeById($id)
    {
        $queryBuilder = $this->createQueryBuilder('collection')
            ->select('collectionType.key')
            ->leftJoin('collection.type', 'collectionType')
            ->where('collection.id = :id')
            ->setParameter('id', $id);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the basic query which selects the ids of a collection for a given
     * set of parameters.
     *
     * @param int $depth
     * @param array $filter
     * @param array $sortBy
     * @param string $select
     *
     * @return Query
     */
    private function getIdsQuery(
        $depth = 0,
        $filter = [],
        $sortBy = [],
        CollectionInterface $collection = null,
        $select = 'collection.id'
    ) {
        $queryBuilder = $this->createQueryBuilder('collection')
            ->select($select)
            ->where('collection.depth <= :depth');

        $collectionDepth = null !== $collection ? $collection->getDepth() : 0;
        $queryBuilder->setParameter('depth', $collectionDepth + $depth);

        if (null !== $collection) {
            $queryBuilder->andWhere('collection.lft BETWEEN :lft AND :rgt AND collection.id != :id');
            $queryBuilder->setParameter('lft', $collection->getLft());
            $queryBuilder->setParameter('rgt', $collection->getRgt());
            $queryBuilder->setParameter('id', $collection->getId());
        }

        if (\array_key_exists('search', $filter) && null !== $filter['search'] ||
            \array_key_exists('locale', $filter) ||
            \count($sortBy) > 0
        ) {
            $queryBuilder->leftJoin('collection.meta', 'collectionMeta');
            $queryBuilder->leftJoin('collection.defaultMeta', 'defaultMeta');
        }

        if (\array_key_exists('search', $filter) && null !== $filter['search']) {
            $queryBuilder->andWhere('collectionMeta.title LIKE :search OR defaultMeta.locale != :locale');
            $queryBuilder->setParameter('search', '%' . $filter['search'] . '%');
        }

        if (\array_key_exists('locale', $filter)) {
            $queryBuilder->andWhere('collectionMeta.locale = :locale OR defaultMeta.locale != :locale');
            $queryBuilder->setParameter('locale', $filter['locale']);
        }

        if (\array_key_exists('systemCollections', $filter) && !$filter['systemCollections']) {
            $queryBuilder->leftJoin('collection.type', 'collectionType');
            $queryBuilder->andWhere('collectionType.key != :type');
            $queryBuilder->setParameter('type', SystemCollectionManagerInterface::COLLECTION_TYPE);
        }

        if (\count($sortBy) > 0) {
            foreach ($sortBy as $column => $order) {
                $queryBuilder->addOrderBy(
                    'collectionMeta.' . $column,
                    ('asc' === \strtolower($order) ? 'ASC' : 'DESC')
                );
            }
        }

        $queryBuilder->addOrderBy('collection.id', 'ASC');

        if (\array_key_exists('limit', $filter)) {
            $queryBuilder->setMaxResults($filter['limit']);
        }
        if (\array_key_exists('offset', $filter)) {
            $queryBuilder->setFirstResult($filter['offset']);
        }

        return $queryBuilder->getQuery();
    }

    public function findIdByMediaId(int $mediaId): ?int
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->from(MediaInterface::class, 'media')
            ->select('IDENTITY(media.collection)')
            ->where('media.id = :mediaId')
            ->setParameter('mediaId', $mediaId);

        try {
            return (int) $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function setAccessControlQueryEnhancer(AccessControlQueryEnhancer $accessControlQueryEnhancer)
    {
        $this->accessControlQueryEnhancer = $accessControlQueryEnhancer;
    }

    public function findDescendantIdsById($id)
    {
        $queryBuilder = $this->createQueryBuilder('subCollection')
            ->select('subCollection.id')
            ->from($this->_entityName, 'collection')
            ->andWhere('collection.id = :id')
            ->andWhere('subCollection.lft > collection.lft AND subCollection.rgt < collection.rgt')
            ->setParameter('id', $id);

        return \array_map(function($collection) {
            return (int) $collection['id'];
        }, $queryBuilder->getQuery()->getScalarResult());
    }

    public function supportsDescendantType(string $type): bool
    {
        return $this->getClassName() === $type;
    }

    private function createChildCollectionsQueryBuilder(string $alias, int $rootCollectionId): QueryBuilder
    {
        $rootCollectionAlias = $alias . '_rootCollection';

        return $this->createQueryBuilder($alias)
            ->innerJoin(
                CollectionInterface::class,
                $rootCollectionAlias,
                Join::WITH,
                $alias . '.lft > ' . $rootCollectionAlias . '.lft AND ' . $alias . '.rgt < ' . $rootCollectionAlias . '.rgt'
            )
            ->where($rootCollectionAlias . '.id = :id')
            ->setParameter('id', $rootCollectionId);
    }

    /**
     * @return array<array{id: int, resourceKey: string, depth: int}>
     */
    public function findChildCollectionResourcesOfRootCollection(int $rootCollectionId): array
    {
        return $this->createChildCollectionsQueryBuilder('collection', $rootCollectionId)
            ->select('collection.id AS id')
            ->addSelect('\'' . CollectionInterface::RESOURCE_KEY . '\' AS resourceKey')
            ->addSelect('collection.depth AS depth')
            ->distinct()
            ->orderBy('collection.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return int[]
     */
    public function findChildCollectionIdsOfRootCollection(int $rootCollectionId): array
    {
        $childCollectionIds = $this->createChildCollectionsQueryBuilder('collection', $rootCollectionId)
            ->select('collection.id AS id')
            ->distinct()
            ->orderBy('collection.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return \array_column($childCollectionIds, 'id');
    }

    public function countChildCollectionsOfRootCollection(int $rootCollectionId): int
    {
        return $this->createChildCollectionsQueryBuilder('collection', $rootCollectionId)
            ->select('COUNT(collection.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createPermittedChildCollectionsQueryBuilder(
        string $alias,
        int $id,
        UserInterface $user,
        int $permission
    ): QueryBuilder {
        $qb = $this->createChildCollectionsQueryBuilder($alias, $id);

        $this->accessControlQueryEnhancer->enhance(
            $qb,
            $user,
            $permission,
            CollectionEntity::class,
            $alias
        );

        return $qb;
    }

    private function createUnauthorizedChildCollectionsQueryBuilder(
        string $alias,
        int $id,
        UserInterface $user,
        int $permission
    ): QueryBuilder {
        $qb = $this->createChildCollectionsQueryBuilder($alias, $id);

        $permittedChildCollectionsQb = $this
            ->createPermittedChildCollectionsQueryBuilder('permittedCollection', $id, $user, $permission)
            ->select('permittedCollection.id')
            ->distinct()
            ->orderBy('permittedCollection.id', 'ASC');

        $qb->andWhere($alias . '.id NOT IN (' . $permittedChildCollectionsQb->getDQL() . ')');

        /** @var Query\Parameter $parameter */
        foreach ($permittedChildCollectionsQb->getParameters() as $parameter) {
            $qb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
        }

        return $qb;
    }

    /**
     * @return array<array{id: int, resourceKey: string, title: string|null}>
     */
    public function findUnauthorizedChildCollectionResourcesOfRootCollection(
        int $id,
        UserInterface $user,
        int $permission,
        ?int $maxResults = null
    ): array {
        return $this
            ->createUnauthorizedChildCollectionsQueryBuilder('collection', $id, $user, $permission)
            ->select('collection.id AS id')
            ->addSelect('\'' . CollectionInterface::RESOURCE_KEY . '\' AS resourceKey')
            ->addSelect('meta.title AS title')
            ->distinct()
            ->leftJoin('collection.defaultMeta', 'meta')
            ->orderBy('collection.id', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getArrayResult();
    }

    public function countUnauthorizedChildCollectionsOfRootCollection(int $id, UserInterface $user, int $permission): int
    {
        return $this
            ->createUnauthorizedChildCollectionsQueryBuilder('collection', $id, $user, $permission)
            ->select('COUNT(collection.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
