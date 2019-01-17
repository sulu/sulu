<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
    /**
     * @var ?FieldMetadata
     */
    private $field;

    /**
     * @var string
     */
    private $glue;

    /**
     * @var bool
     */
    private $distinct;

    public function setField(?FieldMetadata $field)
    {
        $this->field = $field;
    }

    public function getField(): ?FieldMetadata
    {
        return $this->field;
    }

    public function setGlue(string $glue)
    {
        $this->glue = $glue;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }

    public function setDistinct(bool $distinct)
    {
        $this->distinct = $distinct;
    }

    public function getDistinct(): bool
    {
        return $this->distinct;
    }
}
