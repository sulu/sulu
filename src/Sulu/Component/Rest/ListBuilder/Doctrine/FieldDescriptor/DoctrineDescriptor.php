<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor;

use Sulu\Component\Rest\ListBuilder\Doctrine\EncodeAliasTrait;

/**
 * This class describes a doctrine case.
 */
class DoctrineDescriptor
{
    use EncodeAliasTrait;

    /**
     * @var string
     */
    public $entityName;

    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var DoctrineJoinDescriptor[]
     */
    public $joins;

    public function __construct($entityName, $fieldName, array $joins = [])
    {
        $this->entityName = $entityName;
        $this->fieldName = $fieldName;
        $this->joins = $joins;
    }

    /**
     * Returns select statement for case.
     *
     * @return string
     */
    public function getSelect()
    {
        return sprintf('%s.%s', $this->encodeAlias($this->entityName), $this->fieldName);
    }

    /**
     * Returns necessary joins.
     *
     * @return DoctrineJoinDescriptor[]
     */
    public function getJoins()
    {
        return $this->joins;
    }
}
