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
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\AbstractDoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Expression\WhereExpressionInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

/**
 * Represents a WHERE expression for doctrine - needs a field, a value and a comparator.
 */
class DoctrineWhereExpression extends AbstractDoctrineExpression implements WhereExpressionInterface
{
    /**
     * Field descriptor used for comparison.
     *
     * @var AbstractDoctrineFieldDescriptor
     */
    protected $field;

    /**
     * Value which is used to compare.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Comparator to compare values.
     *
     * @var AbstractDoctrineFieldDescriptor
     */
    protected $comparator;

    public function __construct(
        AbstractDoctrineFieldDescriptor $field,
        $value,
        $comparator = ListbuilderInterface::WHERE_COMPARATOR_EQUAL
    ) {
        $this->field = $field;
        $this->value = $value;
        $this->comparator = $comparator;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatement(QueryBuilder $queryBuilder)
    {
        $paramName = $this->getFieldName() . $this->getUniqueId();

        if ($this->getValue() === null) {
            return $this->field->getSelect() . ' ' . $this->convertNullComparator($this->getComparator());
        } elseif ($this->getComparator() === 'LIKE') {
            $queryBuilder->setParameter($paramName, '%' . $this->getValue() . '%');
        } elseif (in_array($this->getComparator(), ['and', 'or']) && is_array($this->getValue())) {
            $statement = [];
            $value = $this->getValue();
            for ($i = 0, $count = count($value); $i < $count; ++$i) {
                $statement[] = sprintf('%s = :%s%s', $this->field->getWhere(), $paramName, $i);
                $queryBuilder->setParameter($paramName . $i, $value[$i]);
            }

            return implode(' ' . $this->getComparator() . ' ', $statement);
        } else {
            $queryBuilder->setParameter($paramName, $this->getValue());
        }

        return $this->field->getWhere() . ' ' . $this->getComparator() . ' :' . $paramName;
    }

    /**
     * @param $comparator
     *
     * @return string
     */
    protected function convertNullComparator($comparator)
    {
        switch ($comparator) {
            case ListBuilderInterface::WHERE_COMPARATOR_EQUAL:
                return 'IS NULL';
            case ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL:
                return 'IS NOT NULL';
            default:
                return $comparator;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getComparator()
    {
        return $this->comparator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName()
    {
        return $this->field->getName();
    }
}
