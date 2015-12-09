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

/**
 * @see Doctrine\ODM\PHPCR\Query\Builder\Ordering
 */
class Ordering extends OperandFactory
{
    /**
     * @var string
     */
    protected $order;

    public function __construct(AbstractNode $parent, $order)
    {
        $this->order = $order;
        parent::__construct($parent);
    }

    /**
     * {@inheritdoc}
     */
    public function getCardinalityMap()
    {
        return [
            self::NT_OPERAND_DYNAMIC => [1, 1],
        ];
    }

    /**
     * @see Doctrine\ODM\PHPCR\Query\Builder\Ordering::getOrder
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeType()
    {
        return self::NT_ORDERING;
    }
}
