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

/**
 * Represents a IN expression for doctrine - needs a field and an array of values
 */
class DoctrineInExpression extends AbstractDoctrineExpression
{
    /**
     * Name of the field which should be compared
     *
     * @var $fieldName string
     */
    protected $fieldName;

    /**
     * Array values to compare
     * @var $values array
     */
    protected $values;

    /**
     * DoctrineInExpression constructor.
     *
     * @param string $fieldName
     * @param array $values
     */
    public function __construct($fieldName, array $values)
    {
        $this->values = $values;
        $this->fieldName = $fieldName;
    }

    /**
     *  Returns a statement for an expression
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return string
     */
    public function getStatement(QueryBuilder $queryBuilder)
    {
        $paramName = $this->getFieldName() . uniqid(true);
        $values = $this->filterNullValues($this->getValues());
        $statement = '';

        if (count($values) > 0) {
            $queryBuilder->setParameter($paramName, implode(',', $values));
            $statement = ' ' . $this->getFieldName() . ' IN (:' . $paramName . ') ';

            if (array_search(null, $this->getValues())) {
                $statement .= ' OR ' . $paramName . ' IS NULL ';
            }
        } elseif (array_search(null, $this->getValues())) { // only null in values array
            $statement .= ' ' . $paramName . ' IS NULL ';
        }

        return $statement;
    }

    /**
     * Returns a new array without null values
     *
     * @param array $values
     *
     * @return array
     */
    function filterNullValues(array $values)
    {
        $result = array_filter(
            $values,
            function ($val) {
                return ($val || $val === 0 || $val === false);
            }
        );

        return $result;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
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
