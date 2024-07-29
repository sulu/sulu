<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

/**
 * Container for field-metadata.
 */
class FieldMetadata
{
    /**
     * @var JoinMetadata[]
     */
    private array $joins = [];

    /**
     * @param string $name
     * @param string $entityName
     */
    public function __construct(private $name, private $entityName)
    {
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
     *
     * @return void
     */
    public function setJoins(array $joins)
    {
        $this->joins = $joins;
    }

    /**
     * @return void
     */
    public function addJoin(JoinMetadata $join)
    {
        $this->joins[] = $join;
    }
}
