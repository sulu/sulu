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
 * Represents a BETWEEN expression for doctrine - needs a field and two values
 */
class DoctrineBetweenExpression extends AbstractDoctrineExpression
{
    /**
     * Name of the field which should be compared
     *
     * @var $fieldName string
     */
    protected $fieldName;

    /**
     * @var $start
     */
    protected $start;

    /**
     * @var $end
     */
    protected $end;

    /**
     * DoctrineInExpression constructor.
     *
     * @param string $fieldName
     * @param $start
     * @param $end
     *
     */
    public function __construct($fieldName, $start, $end)
    {
        $this->start = $start;
        $this->end = $end;
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
        $paramName1 = $this->getFieldName() . uniqid(true);
        $paramName2 = $this->getFieldName() . uniqid(true);
        $queryBuilder->setParameter($paramName1, $this->getStart());
        $queryBuilder->setParameter($paramName2, $this->getEnd());

        return ' ' . $this->getFieldName() . ' BETWEEN :' . $paramName1 . ' AND :' . $paramName2 . ' ';
    }

    /**
     * @return array
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return array
     */
    public function getEnd()
    {
        return $this->end;
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
