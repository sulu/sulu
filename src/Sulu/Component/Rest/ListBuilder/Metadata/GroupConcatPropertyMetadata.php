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
 * Describes a field which is grouped and concatenated.
 */
class GroupConcatPropertyMetadata extends AbstractPropertyMetadata
{
    private ?FieldMetadata $field;

    private string $glue;

    private bool $distinct;

    public function setField(?FieldMetadata $field): void
    {
        $this->field = $field;
    }

    public function getField(): ?FieldMetadata
    {
        return $this->field;
    }

    public function setGlue(string $glue): void
    {
        $this->glue = $glue;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }

    public function setDistinct(bool $distinct): void
    {
        $this->distinct = $distinct;
    }

    public function getDistinct(): bool
    {
        return $this->distinct;
    }
}
