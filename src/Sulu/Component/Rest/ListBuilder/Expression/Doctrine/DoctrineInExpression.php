<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Expression\InExpressionInterface;

/**
 * Represents a IN expression for doctrine - needs a field and an array of values.
 */
class DoctrineInExpression extends AbstractDoctrineExpression implements InExpressionInterface
{
    /**
     * Field descriptor used for comparison.
     *
     * @var DoctrineFieldDescriptorInterface
     */
    protected $field;

    /**
     * Array values to compare.
     *
     * @var array
     */
    protected $values;

    /**
     * DoctrineInExpression constructor.
     *
     * @param DoctrineFieldDescriptorInterface $field
     * @param array $values
     */
    public function __construct(DoctrineFieldDescriptorInterface $field, array $values)
    {
        $this->values = $values;
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatement(QueryBuilder $queryBuilder)
    {
        $paramName = $this->getFieldName() . $this->getUniqueId();
        $values = $this->filterNullValues($this->getValues());
        $statement = '';

        if (count($values) > 0) {
            $queryBuilder->setParameter($paramName, $values);
            $statement = $this->field->getSelect() . ' IN (:' . $paramName . ')';

            if (array_search(null, $this->getValues())) {
                $statement .= ' OR ' . $this->field->getSelect() . ' IS NULL';
            }
        } elseif (array_search(null, $this->getValues())) { // only null in values array
            $statement .= $paramName . ' IS NULL';
        }

        return $statement;
    }

    /**
     * Returns a new array without null values.
     *
     * @param array $values
     *
     * @return array
     */
    protected function filterNullValues(array $values)
    {
        $result = array_filter(
            $values,
            function ($val) {
                return $val || $val === 0 || $val === false;
            }
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName()
    {
        return $this->field->getName();
    }
}
