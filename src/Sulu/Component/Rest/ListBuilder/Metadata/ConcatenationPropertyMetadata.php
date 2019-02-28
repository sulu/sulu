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
 * Describes a field which is concatenated from other fields.
 */
class ConcatenationPropertyMetadata extends AbstractPropertyMetadata
{
    /**
     * @var FieldMetadata[]
     */
    private $fields = [];

    /**
     * @var string
     */
    private $glue;

    public function setGlue(string $glue)
    {
        $this->glue = $glue;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(FieldMetadata $field)
    {
        $this->fields[] = $field;
    }
}
