<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

/**
 * Represents a WHERE expression for doctrine - needs a field, a value and a comparator
 */
class DoctrineWhereExpression extends AbstractDoctrineExpression
{
    /**
     * Name of the field which should be compared
     *
     * @var $fieldName string
     */
    protected $fieldName;

    /**
     * Value which is used to compare
     *
     * @var $value
     */
    protected $value;

    /**
     * Comparator to compare values
     *
     * @var $comparator $string
     */
    protected $comparator;

    function __construct($fieldName, $value, $comparator = ListbuilderInterface::WHERE_COMPARATOR_EQUAL)
    {
        $this->fieldName = $fieldName;
        $this->value = $value;
        $this->comparator = $comparator;
    }

    /**
     * Returns a statement for an expression
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return string
     */
    public function getStatement(QueryBuilder $queryBuilder)
    {
        $paramName = $this->getFieldName() . uniqid(true);

        if ($this->getValue() === null) {
            return ' ' . $this->getFieldName() . ' ' . $this->convertNullComparator($this->getComparator());
        } elseif ($this->getComparator() === 'LIKE') {
            $queryBuilder->setParameter($paramName, '%' . $this->getValue() . '%');
        } else {
            $queryBuilder->setParameter($paramName, $this->getValue());
        }

        return ' ' . $this->getFieldName() . ' ' . $this->getComparator() . ' :' . $paramName . ' ';
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
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getComparator()
    {
        return $this->comparator;
    }

    /**
     * Returns the fieldname
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}

