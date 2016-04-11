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

use Sulu\Component\Rest\ListBuilder\Expression\Exception\InsufficientExpressionsException;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

/**
 * Combines an array of expressions with AND.
 */
class DoctrineAndExpression extends DoctrineConjunctionExpression
{
    /**
     * @var string
     */
    protected $conjunction = ListBuilderInterface::CONJUNCTION_AND;

    /**
     * @param AbstractDoctrineExpression[] $expressions
     *
     * @throws InsufficientExpressionsException
     */
    public function __construct(array $expressions)
    {
        parent::__construct($this->conjunction, $expressions);
    }
}
