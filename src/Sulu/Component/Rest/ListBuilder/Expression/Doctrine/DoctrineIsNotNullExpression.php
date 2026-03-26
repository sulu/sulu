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
use Sulu\Component\Rest\ListBuilder\Expression\IsNotNullExpressionInterface;

class DoctrineIsNotNullExpression extends AbstractDoctrineExpression implements IsNotNullExpressionInterface
{
    public function __construct(protected DoctrineFieldDescriptorInterface $field)
    {
    }

    public function getStatement(QueryBuilder $queryBuilder)
    {
        return $this->field->getSelect() . ' IS NOT NULL';
    }

    public function getFieldName()
    {
        return $this->field->getName();
    }
}
