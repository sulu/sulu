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
 * Describes a many-to-many field which will be evaluated with COUNT function.
 */
class CountPropertyMetadata extends AbstractPropertyMetadata
{
    /**
     * @var FieldMetadata
     */
    private $field;

    public function setField(FieldMetadata $field)
    {
        $this->field = $field;
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
}
