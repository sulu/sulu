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
    private string $name;

    private string $entityName;

    /**
     * @var JoinMetadata[]
     */
    private array $joins = [];

    public function __construct($name, $entityName)
    {
        $this->name = $name;
        $this->entityName = $entityName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return JoinMetadata[]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @param JoinMetadata[] $joins
     */
    public function setJoins(array $joins): void
    {
        $this->joins = $joins;
    }

    public function addJoin(JoinMetadata $join): void
    {
        $this->joins[] = $join;
    }
}
