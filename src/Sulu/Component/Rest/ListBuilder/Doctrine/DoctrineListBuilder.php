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
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The listbuilder implementation for doctrine.
 */
class DoctrineListBuilder extends AbstractListBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * The name of the entity to build the list for.
     *
     * @var string
     */
    private $entityName;

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $selectFields = array();

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $searchFields = array();

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $whereFields = array();

    /**
     * @var AbstractDoctrineFieldDescriptor[]
     */
    protected $inFields = array();

    /**
     * @var AbstractDoctrineFieldDescriptor
     */
    protected $sortField;

    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $queryBuilder;

    public function __construct(EntityManager $em, $entityName, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->entityName = $entityName;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        // TODO: remove uneccessary joins from count!

        $entityId = $this->entityName . '.id';
        $this->queryBuilder = $this->createQueryBuilder()
            ->select('count(' . $entityId . ')');

        $result = $this->queryBuilder->getQuery()->getScalarResult();
        if (!$result) {
            return 0;
        }

        // in case result has multiple results,
        // group by separated result into multiple results,
        // so return count of results
        if (($temp = count($result)) > 1) {
            $result = $temp;
        } else {
            // reset array indices
            $result = array_values($result[0]);
            $result = $result[0];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        // emit listbuilder.create event
        $event = new ListBuilderCreateEvent($this);
        $this->eventDispatcher->dispatch(ListBuilderEvents::LISTBUILDER_CREATE, $event);

        $this->queryBuilder = $this->createQueryBuilder();

        foreach ($this->selectFields as $field) {
            $this->queryBuilder->addSelect($field->getSelect() . ' AS ' . $field->getName());
        }

        if ($this->limit != null) {
            $this->queryBuilder->setMaxResults($this->limit)->setFirstResult($this->limit * ($this->page - 1));
        }

        if ($this->sortField != null) {
            $this->queryBuilder->orderBy($this->sortField->getSelect(), $this->sortOrder);
        }

        return $this->queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Returns all the joins required for the query.
     *
     * @return DoctrineJoinDescriptor[]
     */
    private function getJoins()
    {
        $joins = array();

        if ($this->sortField != null) {
            $joins = array_merge($joins, $this->sortField->getJoins());
        }

        foreach ($this->selectFields as $field) {
            $joins = array_merge($joins, $field->getJoins());
        }

        foreach ($this->searchFields as $searchField) {
            $joins = array_merge($joins, $searchField->getJoins());
        }

        foreach ($this->whereFields as $whereField) {
            $joins = array_merge($joins, $whereField->getJoins());
        }

        foreach ($this->inFields as $inField) {
            $joins = array_merge($joins, $inField->getJoins());
        }

        return $joins;
    }

    private function createSubQuery()
    {
        // TODO: what about group by fields

        $filterFields = array_merge($this->whereFields, $this->inFields, $this->betweenFields, $this->searchFields);
        $joins = $this->getJoins();

        $addJoins = array();
        foreach ($joins as $entity => $join) {
            if (array_search($entity, $filterFields) != false ||
                $join->getJoinConditionMethod() == DoctrineJoinDescriptor::JOIN_METHOD_INNER
            ) {
                $addJoins[$entity] = $join;
            }
        }

        $this->createQueryBuilder($addJoins);
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createQueryBuilder($joins = null)
    {
        $this->queryBuilder = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->entityName);

        if ($joins !== null) {
            foreach ($this->getJoins() as $entity => $join) {
                switch ($join->getJoinMethod()) {
                    case DoctrineJoinDescriptor::JOIN_METHOD_LEFT:
                        $this->queryBuilder->leftJoin(
                            $join->getJoin(),
                            $entity,
                            $join->getJoinConditionMethod(),
                            $join->getJoinCondition()
                        );
                        break;
                    case DoctrineJoinDescriptor::JOIN_METHOD_INNER:
                        $this->queryBuilder->innerJoin(
                            $join->getJoin(),
                            $entity,
                            $join->getJoinConditionMethod(),
                            $join->getJoinCondition()
                        );
                        break;
                }
            }
        }

        // set where
        if (!empty($this->whereFields)) {
            $this->addWheres($this->whereFields, $this->whereValues, $this->whereComparators, $this->whereConjunctions);
        }

        if (!empty($this->groupByFields)) {
            foreach ($this->groupByFields as $fields) {
                $this->queryBuilder->groupBy($fields->getSelect());
            }
        }

        // set in
        if (!empty($this->inFields)) {
            $this->addIns($this->inFields, $this->inValues);
        }

        // set between
        if (!empty($this->betweenFields)) {
            $this->addBetweens($this->betweenFields, $this->betweenValues, $this->betweenConjunctions);
        }

        if ($this->search != null) {
            $searchParts = array();
            foreach ($this->searchFields as $searchField) {
                $searchParts[] = $searchField->getSelect() . ' LIKE :search';
            }

            $this->queryBuilder->andWhere('(' . implode(' OR ', $searchParts) . ')');
            $this->queryBuilder->setParameter('search', '%' . $this->search . '%');
        }

        return $this->queryBuilder;
    }

    /**
     * adds where statements for in-clauses.
     *
     * @param array $inFields
     * @param array $inValues
     */
    protected function addIns(array $inFields, array $inValues)
    {
        $inParts = array();
        foreach ($inFields as $inField) {
            $inPart = $inField->getSelect() . ' IN (:' . $inField->getName() . ')';
            $this->queryBuilder->setParameter($inField->getName(), $inValues[$inField->getName()]);

            // null values
            if (array_search(null, $inValues[$inField->getName()])) {
                $inPart .= ' OR ' . $inField->getSelect() . ' IS NULL';
            }

            $inParts[] = $inPart;
        }

        $this->queryBuilder->andWhere('(' . implode(' AND ', $inParts) . ')');
    }

    /**
     * adds where statements for in-clauses.
     *
     * @param array $betweenFields
     * @param array $betweenValues
     * @param array $betweenConjunctions
     */
    protected function addBetweens(array $betweenFields, array $betweenValues, array $betweenConjunctions)
    {
        $betweenParts = array();
        $firstConjunction = null;

        foreach ($betweenFields as $betweenField) {
            $conjunction = ' ' . $betweenConjunctions[$betweenField->getName()] . ' ';

            if (!$firstConjunction) {
                $firstConjunction = $betweenConjunctions[$betweenField->getName()];
                $conjunction = '';
            }

            $betweenParts[] = $conjunction . $betweenField->getSelect() .
                ' BETWEEN :' . $betweenField->getName() . '1' .
                ' AND :' . $betweenField->getName() . '2';

            $values = $betweenValues[$betweenField->getName()];
            $this->queryBuilder->setParameter($betweenField->getName() . '1', $values[0]);
            $this->queryBuilder->setParameter($betweenField->getName() . '2', $values[1]);
        }

        $betweenString = implode('', $betweenParts);
        if (strtoupper($firstConjunction) === self::CONJUNCTION_OR) {
            $this->queryBuilder->orWhere('(' . $betweenString . ')');
        } else {
            $this->queryBuilder->andWhere('(' . $betweenString . ')');
        }
    }

    /**
     * Sets where statement
     *
     * @param array $whereFields
     * @param array $whereValues
     * @param array $whereComparators
     * @param array $whereConjunctions
     */
    protected function addWheres(
        array $whereFields,
        array $whereValues,
        array $whereComparators,
        array $whereConjunctions
    ) {
        $whereParts = array();
        $firstConjunction = null;

        foreach ($whereFields as $whereField) {
            $conjunction = ' ' . $whereConjunctions[$whereField->getName()] . ' ';
            $value = $whereValues[$whereField->getName()];
            $comparator = $whereComparators[$whereField->getName()];

            if (!$firstConjunction) {
                $firstConjunction = $whereConjunctions[$whereField->getName()];
                $conjunction = '';
            }

            $whereParts[] = $this->createWherePart($value, $whereField, $conjunction, $comparator);
        }

        $whereString = implode('', $whereParts);
        if (strtoupper($firstConjunction) === self::CONJUNCTION_OR) {
            $this->queryBuilder->orWhere('(' . $whereString . ')');
        } else {
            $this->queryBuilder->andWhere('(' . $whereString . ')');
        }
    }

    /**
     * Creates a partial where statement
     *
     * @param $value
     * @param $whereField
     * @param $conjunction
     * @param $comparator
     *
     * @return string
     */
    protected function createWherePart($value, $whereField, $conjunction, $comparator)
    {
        if ($value === null) {

            return $conjunction . $whereField->getSelect() . ' ' . $this->convertNullComparator($comparator);
        } elseif ($comparator === 'LIKE') {
            $this->queryBuilder->setParameter($whereField->getName(), '%' . $value . '%');
        } else {
            $this->queryBuilder->setParameter($whereField->getName(), $value);
        }

        return $conjunction . $whereField->getSelect() . ' ' . $comparator . ' :' . $whereField->getName();
    }

    /**
     * @param $comparator
     *
     * @return string
     */
    protected function convertNullComparator($comparator)
    {
        switch ($comparator) {
            case self::WHERE_COMPARATOR_EQUAL:
                return 'IS NULL';
            case self::WHERE_COMPARATOR_UNEQUAL:
                return 'IS NOT NULL';
            default:
                return $comparator;
        }
    }
}
