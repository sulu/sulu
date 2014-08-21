<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;
use Sulu\Component\Rest\ListBuilder\AbstractListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\AbstractDoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;

/**
 * The listbuilder implementation for doctrine
 * @package Sulu\Component\Rest\ListBuilder\Doctrine
 */
class DoctrineListBuilder extends AbstractListBuilder
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The name of the entity to build the list for
     * @var string
     */
    private $entityName;

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $fields = array();

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $searchFields = array();

    /**
     * @var AbstractDoctrineFieldDescriptor
     */
    protected $sortField;


    public function __construct(EntityManager $em, $entityName)
    {
        $this->em = $em;
        $this->entityName = $entityName;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        $entityId = $this->entityName . '.id';
        $qb = $this->createQueryBuilder()
            ->select('count(' . $entityId . ')');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $qb = $this->createQueryBuilder();

        foreach ($this->fields as $field) {
            $qb->addSelect($field->getSelect() . ' AS ' . $field->getName());
        }

        if ($this->limit != null) {
            $qb->setMaxResults($this->limit)->setFirstResult($this->limit * ($this->page - 1));
        }

        if ($this->sortField != null) {
            $qb->orderBy($this->sortField->getName(), $this->sortOrder);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Returns all the joins required for the query
     * @return DoctrineJoinDescriptor[]
     */
    private function getJoins()
    {
        $joins = array();

        if ($this->sortField != null) {
            $joins = array_merge($joins, $this->sortField->getJoins());
        }

        foreach ($this->fields as $field) {
            $joins = array_merge($joins, $field->getJoins());
        }

        foreach ($this->searchFields as $searchField) {
            $joins = array_merge($joins, $searchField->getJoins());
        }

        foreach ($this->whereFields as $whereField) {
            $joins = array_merge($joins, $whereField->getJoins());
        }

        foreach ($this->whereNotFields as $whereNotField) {
            $joins = array_merge($joins, $whereNotField->getJoins());
        }

        return $joins;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createQueryBuilder()
    {
        $qb = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->entityName);

        foreach ($this->getJoins() as $entity => $join) {
            switch ($join->getJoinMethod()) {
                case DoctrineJoinDescriptor::JOIN_METHOD_LEFT:
                    $qb->leftJoin(
                        $join->getJoin(),
                        $entity,
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
                case DoctrineJoinDescriptor::JOIN_METHOD_INNER:
                    $qb->innerJoin(
                        $join->getJoin(),
                        $entity,
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
            }
        }

        // set where
        if (!empty($this->whereFields)) {
            $this->setWheres($this->whereFields, $this->whereValues, $qb, self::WHERE_COMPARATOR_EQUAL);
        }

        // set where not
        if (!empty($this->whereNotFields)) {
            $this->setWheres($this->whereNotFields, $this->whereNotValues, $qb, self::WHERE_COMPARATOR_UNEQUAL);
        }

        if ($this->search != null) {
            $searchParts = array();
            foreach ($this->searchFields as $searchField) {
                $searchParts[] = $searchField->getSelect() . ' LIKE :search';
            }

            $qb->andWhere('(' . implode(' OR ', $searchParts) . ')');
            $qb->setParameter('search', '%' . $this->search . '%');
        }

        return $qb;
    }

    /**
     * sets where statement
     * @param array $whereFields
     * @param array $whereValues
     * @param QueryBuilder $queryBuilder
     * @param string $comparator
     */
    private function setWheres(array $whereFields, array $whereValues, QueryBuilder $queryBuilder, $comparator = self::WHERE_COMPARATOR_EQUAL)
    {
        $whereParts = array();
        foreach ($whereFields as $whereField) {
            $whereParts[] = $whereField->getSelect() . ' ' . $comparator . ' :' . $whereField->getName();
            $queryBuilder->setParameter($whereField->getName(), $whereValues[$whereField->getName()]);
        }
        $queryBuilder->andWhere('(' . implode(' AND ', $whereParts) . ')');
    }
}
