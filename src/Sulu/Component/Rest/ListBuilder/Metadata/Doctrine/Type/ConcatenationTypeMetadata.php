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

/**
 * Describes a field which is concatenated from other fields.
 */
class ConcatenationTypeMetadata
{
    /**
     * @var FieldMetadata[]
     */
    private $fields = [];

    /**
     * @var string
     */
    private $glue;

    public function __construct($glue = ' ')
    {
        $this->glue = $glue;
    }

    /**
     * Returns glue to combine the field values.
     *
     * @return string
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * Returns all fields which should be combined.
     *
     * @return FieldMetadata[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Add a field which should be combined with the other fields.
     *
     * @param FieldMetadata $field
     */
    public function addField(FieldMetadata $field)
    {
        $this->fields[] = $field;
    }
}
