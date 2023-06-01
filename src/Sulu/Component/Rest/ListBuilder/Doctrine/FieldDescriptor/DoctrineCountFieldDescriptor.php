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

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * This class defines the necessary information for a field to resolve it within a Doctrine Query for the ListBuilder.
 *
 * @ExclusionPolicy("all")
 */
class DoctrineCountFieldDescriptor extends DoctrineFieldDescriptor
{
    /**
     * @var bool
     */
    private $distinct;

    public function __construct(
        string $fieldName,
        string $name,
        string $entityName,
        ?string $translation = null,
        array $joins = [],
        string $visibility = FieldDescriptorInterface::VISIBILITY_YES,
        string $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER,
        string $type = '',
        bool $sortable = true,
        bool $distinct = false,
        string $width = FieldDescriptorInterface::WIDTH_AUTO
    ) {
        parent::__construct(
            $fieldName,
            $name,
            $entityName,
            $translation,
            $joins,
            $visibility,
            $searchability,
            $type,
            $sortable,
            $width
        );

        $this->distinct = $distinct;
    }

    public function getSelect()
    {
        return 'COUNT(' . ($this->distinct ? 'DISTINCT ' : '') . $this->encodeAlias($this->getEntityName()) . '.' . $this->getFieldName() . ')';
    }
}
