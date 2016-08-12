<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\AbstractListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderEvents;
use Sulu\Component\Rest\ListBuilder\Expression\BasicExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\ConjunctionExpressionInterface;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\AbstractDoctrineExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineAndExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineBetweenExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineInExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineOrExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Doctrine\DoctrineWhereExpression;
use Sulu\Component\Rest\ListBuilder\Expression\Exception\InvalidExpressionArgumentException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityRepositoryTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The listbuilder implementation for doctrine.
 */
class DoctrineListBuilder extends AbstractListBuilder
{
    use SecuredEntityRepositoryTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $permissions;

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
     * @var DoctrineFieldDescriptorInterface[]
     */
    protected $selectFields = [];

    /**
     * @var DoctrineFieldDescriptorInterface[]
     */
    protected $searchFields = [];

    /**
     * @var AbstractDoctrineExpression[]
     */
    protected $expressions = [];

    /**
     * Array of unique field descriptors from expressions.
     *
     * @var array
     */
    protected $expressionFields = [];

    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var bool
     */
    private $distinct = false;

    /**
     * @var FieldDescriptorInterface
     */
    private $idField;

    public function __construct(
        EntityManager $em,
        $entityName,
        EventDispatcherInterface $eventDispatcher,
        array $permissions
    ) {
        $this->em = $em;
        $this->entityName = $entityName;
        $this->eventDispatcher = $eventDispatcher;
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $subQueryBuilder = $this->createSubQueryBuilder('COUNT(' . $this->entityName . '.id)');

        $result = $subQueryBuilder->getQuery()->getScalarResult();
        $numResults = count($result);
        if ($numResults > 1) {
            return $numResults;
        } elseif ($numResults == 1) {
            $result = array_values($result[0]);

            return (int) $result[0];
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // emit listbuilder.create event
        $event = new ListBuilderCreateEvent($this);
        $this->eventDispatcher->dispatch(ListBuilderEvents::LISTBUILDER_CREATE, $event);
        $this->expressionFields = $this->getUniqueExpressionFieldDescriptors($this->expressions);

        // first create simplified id query
        // select ids with all necessary filter data
        $ids = $this->findIdsByGivenCriteria();

        // if no results are found - return
        if (count($ids) < 1) {
            return [];
        }

        // now select all data
        $this->queryBuilder = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->entityName);
        $this->assignJoins($this->queryBuilder);

        // Add all select fields
        foreach ($this->selectFields as $field) {
            $this->queryBuilder->addSelect($field->getSelect() . ' AS ' . $field->getName());
        }
        // group by
        $this->assignGroupBy($this->queryBuilder);
        // assign sort-fields
        $this->assignSortFields($this->queryBuilder);

        // use ids previously selected ids for query
        $select = $this->entityName . '.id';
        if (null !== $this->idField) {
            $select = $this->idField->getSelect();
        }
        $this->queryBuilder->where($select . ' IN (:ids)')
            ->setParameter('ids', $ids);

        $this->queryBuilder->distinct($this->distinct);

        return $this->queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Function that finds all IDs of entities that match the
     * search criteria.
     *
     * @return array
     */
    protected function findIdsByGivenCriteria()
    {
        $select = null;
        if (null !== $this->idField) {
            $select = $this->idField->getSelect();
        }

        $subQueryBuilder = $this->createSubQueryBuilder($select);
        if ($this->limit != null) {
            $subQueryBuilder->setMaxResults($this->limit)->setFirstResult($this->limit * ($this->page - 1));
        }

        foreach ($this->sortFields as $index => $sortField) {
            $subQueryBuilder->addSelect($sortField->getSelect() . ' AS ' . $sortField->getName());
        }

        $this->assignSortFields($subQueryBuilder);
        $ids = $subQueryBuilder->getQuery()->getArrayResult();
        // if no results are found - return
        if (count($ids) < 1) {
            return [];
        }
        $ids = array_map(
            function ($array) {
                return $array['id'];
            },
            $ids
        );

        return $ids;
    }

    /**
     * Assigns ORDER BY clauses to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assignSortFields($queryBuilder)
    {
        // if no sort has been assigned add order by id ASC as default
        if (count($this->sortFields) === 0) {
            $queryBuilder->addOrderBy($this->entityName . '.id', 'ASC');
        }

        foreach ($this->sortFields as $index => $sortField) {
            $statement = $sortField->getSelect() . ' AS ' . $sortField->getName();
            if (!$this->hasSelectStatement($queryBuilder, $statement)) {
                $queryBuilder->addSelect($sortField->getSelect() . ' AS HIDDEN ' . $sortField->getName());
            }

            $queryBuilder->addOrderBy($sortField->getName(), $this->sortOrders[$index]);
        }
    }

    protected function hasSelectStatement(QueryBuilder $queryBuilder, $statement)
    {
        foreach ($queryBuilder->getDQLPart('select') as $selectPart) {
            foreach ($selectPart->getParts() as $part) {
                if ($part === $statement) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sets group by fields to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assignGroupBy($queryBuilder)
    {
        if (!empty($this->groupByFields)) {
            foreach ($this->groupByFields as $fields) {
                $queryBuilder->groupBy($fields->getSelect());
            }
        }
    }

    /**
     * Returns all the joins required for the query.
     *
     * @return DoctrineJoinDescriptor[]
     */
    protected function getJoins()
    {
        $joins = [];
        $fields = array_merge($this->sortFields, $this->selectFields, $this->searchFields, $this->expressionFields);

        foreach ($fields as $field) {
            $joins = array_merge($joins, $field->getJoins());
        }

        return $joins;
    }

    /**
     * Returns all FieldDescriptors that were passed to list builder.
     *
     * @param bool $onlyReturnFilterFields Define if only filtering FieldDescriptors should be returned
     *
     * @return DoctrineFieldDescriptorInterface[]
     */
    protected function getAllFields($onlyReturnFilterFields = false)
    {
        $fields = array_merge(
            $this->searchFields,
            $this->sortFields,
            $this->getUniqueExpressionFieldDescriptors($this->expressions)
        );

        if ($onlyReturnFilterFields !== true) {
            $fields = array_merge($fields, $this->selectFields);
        }

        return $fields;
    }

    /**
     * Creates a query-builder for sub-selecting ID's.
     *
     * @param null|string $select
     *
     * @return QueryBuilder
     */
    protected function createSubQueryBuilder($select = null)
    {
        if (!$select) {
            $select = $this->entityName . '.id';
        }

        // get all filter-fields
        $filterFields = $this->getAllFields(true);

        // get entity names
        $entityNames = $this->getEntityNamesOfFieldDescriptors($filterFields);

        // get necessary joins to achieve filtering
        $addJoins = $this->getNecessaryJoins($entityNames);

        // create querybuilder and add select
        $queryBuilder = $this->createQueryBuilder($addJoins)->select($select);

        if ($this->user && $this->permission && array_key_exists($this->permission, $this->permissions)) {
            $this->addAccessControl(
                $queryBuilder,
                $this->user,
                $this->permissions[$this->permission],
                $this->entityName,
                $this->entityName
            );
        }

        return $queryBuilder;
    }

    /**
     * Function returns all necessary joins for filtering result.
     *
     * @param string[] $necessaryEntityNames
     *
     * @return DoctrineFieldDescriptorInterface[]
     */
    protected function getNecessaryJoins($necessaryEntityNames)
    {
        $addJoins = [];

        // iterate through all field descriptors to find necessary joins
        foreach ($this->getAllFields() as $key => $field) {
            // if field is in any conditional clause -> add join
            if (($field instanceof DoctrineFieldDescriptor || $field instanceof DoctrineJoinDescriptor) &&
                array_search($field->getEntityName(), $necessaryEntityNames) !== false
                && $field->getEntityName() !== $this->entityName
            ) {
                $addJoins = array_merge($addJoins, $field->getJoins());
            } else {
                // include inner joins
                foreach ($field->getJoins() as $entityName => $join) {
                    if ($join->getJoinMethod() !== DoctrineJoinDescriptor::JOIN_METHOD_INNER &&
                        array_search($entityName, $necessaryEntityNames) === false
                    ) {
                        break;
                    }
                    $addJoins = array_merge($addJoins, [$entityName => $join]);
                }
            }
        }

        return $addJoins;
    }

    /**
     * Returns array of field-descriptor aliases.
     *
     * @param array $filterFields
     *
     * @return string[]
     */
    protected function getEntityNamesOfFieldDescriptors($filterFields)
    {
        $fields = [];

        // filter array for DoctrineFieldDescriptors
        foreach ($filterFields as $field) {
            // add joins of field
            $fields = array_merge($fields, $field->getJoins());

            if ($field instanceof DoctrineFieldDescriptor
                || $field instanceof DoctrineJoinDescriptor
            ) {
                $fields[] = $field;
            }
        }

        $fieldEntityNames = [];
        foreach ($fields as $key => $field) {
            // special treatment for join descriptors
            if ($field instanceof DoctrineJoinDescriptor) {
                $fieldEntityNames[] = $key;
            }
            $fieldEntityNames[] = $field->getEntityName();
        }

        // unify result
        return array_unique($fieldEntityNames);
    }

    /**
     * Creates Querybuilder.
     *
     * @param array|null $joins Define which joins should be made
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createQueryBuilder($joins = null)
    {
        $this->queryBuilder = $this->em->createQueryBuilder()
            ->from($this->entityName, $this->entityName);

        $this->assignJoins($this->queryBuilder, $joins);

        // set expressions
        if (!empty($this->expressions)) {
            foreach ($this->expressions as $expression) {
                $this->queryBuilder->andWhere('(' . $expression->getStatement($this->queryBuilder) . ')');
            }
        }

        $this->assignGroupBy($this->queryBuilder);

        if ($this->search !== null) {
            $searchParts = [];
            foreach ($this->searchFields as $searchField) {
                $searchParts[] = $searchField->getSearch();
            }

            $this->queryBuilder->andWhere('(' . implode(' OR ', $searchParts) . ')');
            $this->queryBuilder->setParameter('search', '%' . str_replace('*', '%', $this->search) . '%');
        }

        return $this->queryBuilder;
    }

    /**
     * Adds joins to querybuilder.
     *
     * @param QueryBuilder $queryBuilder
     * @param array $joins
     */
    protected function assignJoins(QueryBuilder $queryBuilder, array $joins = null)
    {
        if ($joins === null) {
            $joins = $this->getJoins();
        }

        foreach ($joins as $entity => $join) {
            switch ($join->getJoinMethod()) {
                case DoctrineJoinDescriptor::JOIN_METHOD_LEFT:
                    $queryBuilder->leftJoin(
                        $join->getJoin(),
                        $entity,
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
                case DoctrineJoinDescriptor::JOIN_METHOD_INNER:
                    $queryBuilder->innerJoin(
                        $join->getJoin(),
                        $entity,
                        $join->getJoinConditionMethod(),
                        $join->getJoinCondition()
                    );
                    break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createWhereExpression(FieldDescriptorInterface $fieldDescriptor, $value, $comparator)
    {
        if (!$fieldDescriptor instanceof DoctrineFieldDescriptorInterface) {
            throw new InvalidExpressionArgumentException('where', 'fieldDescriptor');
        }

        return new DoctrineWhereExpression($fieldDescriptor, $value, $comparator);
    }

    /**
     * {@inheritdoc}
     */
    public function createInExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        if (!$fieldDescriptor instanceof DoctrineFieldDescriptorInterface) {
            throw new InvalidExpressionArgumentException('in', 'fieldDescriptor');
        }

        return new DoctrineInExpression($fieldDescriptor, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function createBetweenExpression(FieldDescriptorInterface $fieldDescriptor, array $values)
    {
        if (!$fieldDescriptor instanceof DoctrineFieldDescriptorInterface) {
            throw new InvalidExpressionArgumentException('between', 'fieldDescriptor');
        }

        return new DoctrineBetweenExpression($fieldDescriptor, $values[0], $values[1]);
    }

    /**
     * Eliminates duplicated rows.
     *
     * @param bool $flag
     */
    public function distinct($flag = true)
    {
        $this->distinct = $flag;
    }

    /**
     * Set id-field of the "root" entity.
     *
     * @param FieldDescriptorInterface $idField
     */
    public function setIdField(FieldDescriptorInterface $idField)
    {
        $this->idField = $idField;
    }

    /**
     * Returns an array of unique expression field descriptors.
     *
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @return array
     */
    protected function getUniqueExpressionFieldDescriptors(array $expressions)
    {
        if (count($this->expressionFields) === 0) {
            $descriptors = [];
            $uniqueNames = array_unique($this->getAllFieldNames($expressions));
            foreach ($uniqueNames as $uniqueName) {
                $descriptors[] = $this->fieldDescriptors[$uniqueName];
            }

            $this->expressionFields = $descriptors;

            return $descriptors;
        }

        return $this->expressionFields;
    }

    /**
     * Returns all fieldnames used in the expressions.
     *
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @return array
     */
    protected function getAllFieldNames($expressions)
    {
        $fieldNames = [];
        foreach ($expressions as $expression) {
            if ($expression instanceof ConjunctionExpressionInterface) {
                $fieldNames = array_merge($fieldNames, $expression->getFieldNames());
            } elseif ($expression instanceof BasicExpressionInterface) {
                $fieldNames[] = $expression->getFieldName();
            }
        }

        return $fieldNames;
    }

    /**
     * {@inheritdoc}
     */
    public function createAndExpression(array $expressions)
    {
        if (count($expressions) >= 2) {
            return new DoctrineAndExpression($expressions);
        }

        throw new InvalidExpressionArgumentException('and', 'expressions');
    }

    /**
     * {@inheritdoc}
     */
    public function createOrExpression(array $expressions)
    {
        if (count($expressions) >= 2) {
            return new DoctrineOrExpression($expressions);
        }

        throw new InvalidExpressionArgumentException('or', 'expressions');
    }
}
