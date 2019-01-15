<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type;

use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;

/**
 * Describes a field which is concatenated from other fields.
 */
class ConcatenationTypeMetadata extends PropertyMetadata
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
