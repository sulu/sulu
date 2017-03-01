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
 * Describes a field which is grouped and concatenated.
 */
class GroupConcatTypeMetadata
{
    /**
     * @var FieldMetadata
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

    /**
     * @param FieldMetadata $field
     * @param string $glue
     * @param bool $distinct
     */
    public function __construct(FieldMetadata $field, $glue, $distinct)
    {
        $this->field = $field;
        $this->glue = $glue;
        $this->distinct = $distinct;
    }

    /**
     * Returns metadata for field.
     *
     * @return FieldMetadata
     */
    public function getField()
    {
        return $this->field;
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
     * Returns bool if distinct should be used or not.
     *
     * @return bool
     */
    public function getDistinct()
    {
        return $this->distinct;
    }
}
