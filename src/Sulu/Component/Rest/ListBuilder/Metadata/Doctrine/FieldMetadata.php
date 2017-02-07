<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine;

/**
 * Container for field-metadata.
 */
class FieldMetadata
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var JoinMetadata[]
     */
    private $joins = [];

    public function __construct($name, $entityName)
    {
        $this->name = $name;
        $this->entityName = $entityName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return JoinMetadata[]
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @param JoinMetadata[] $joins
     */
    public function setJoins(array $joins)
    {
        $this->joins = $joins;
    }

    /**
     * @param JoinMetadata $join
     */
    public function addJoin(JoinMetadata $join)
    {
        $this->joins[] = $join;
    }
}
