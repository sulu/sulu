<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Query\Builder;

use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder as Doctrine;
use Sulu\Component\Content\Document\Query\QueryBuilderUtil;
use Sulu\Component\Content\Document\Query\SuluNodeInterface;

/**
 * @see Doctrine\ODM\PHPCR\Query\Builder\ConstraintComparison
 */
class ConstraintComparison extends OperandFactory implements SuluNodeInterface
{
    /**
     * @var string
     */
    protected $operator;

    public function __construct(AbstractNode $parent, $operator)
    {
        $this->operator = $operator;
        parent::__construct($parent);
    }

    /**
     * {@inheritdoc}
     */
    public function getCardinalityMap()
    {
        return [
            self::NT_OPERAND_DYNAMIC => ['1', '1'],
            self::NT_OPERAND_STATIC => ['1', '1'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeType()
    {
        return self::NT_CONSTRAINT;
    }

    /**
     * @see Doctrine\ODM\PHPCR\Query\Builder\ConstraintComparison::getOperator
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineInstance()
    {
        return QueryBuilderUtil::addNodeChildren(
            $this,
            new Doctrine\ConstraintComparison($this->getParent(), $this->getOperator())
        );
    }
}
