<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
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
     * @var DoctrineFieldDescriptorInterface
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
     * @var DoctrineFieldDescriptorInterface
     */
    protected $comparator;

    public function __construct(
        DoctrineFieldDescriptorInterface $field,
        $value,
        $comparator = ListBuilderInterface::WHERE_COMPARATOR_EQUAL
    ) {
        $this->field = $field;
        $this->value = $value;
        $this->comparator = $comparator;
    }

    public function getStatement(QueryBuilder $queryBuilder)
    {
        $paramName = $this->getFieldName() . $this->getUniqueId();

        if (null === $this->getValue()) {
            return $this->field->getSelect() . ' ' . $this->convertNullComparator($this->getComparator());
        } elseif ('LIKE' === $this->getComparator()) {
            $queryBuilder->setParameter($paramName, '%' . $this->getValue() . '%');
        } elseif (\in_array($this->getComparator(), ['and', 'or']) && \is_array($this->getValue())) {
            $statement = [];
            $value = $this->getValue();
            for ($i = 0, $count = \count($value); $i < $count; ++$i) {
                $statement[] = \sprintf('%s = :%s%s', $this->field->getWhere(), $paramName, $i);
                $queryBuilder->setParameter($paramName . $i, $value[$i]);
            }

            return \implode(' ' . $this->getComparator() . ' ', $statement);
        } else {
            $queryBuilder->setParameter($paramName, $this->getValue());
        }

        return $this->field->getWhere() . ' ' . $this->getComparator() . ' :' . $paramName;
    }

    /**
     * @param string $comparator
     *
     * @return string
     */
    protected function convertNullComparator($comparator)
    {
        return match ($comparator) {
            ListBuilderInterface::WHERE_COMPARATOR_EQUAL => 'IS NULL',
            ListBuilderInterface::WHERE_COMPARATOR_UNEQUAL => 'IS NOT NULL',
            default => $comparator,
        };
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getComparator()
    {
        return $this->comparator;
    }

    public function getFieldName()
    {
        return $this->field->getName();
    }
}
