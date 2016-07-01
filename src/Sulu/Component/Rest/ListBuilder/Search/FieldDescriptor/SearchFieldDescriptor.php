<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Search\FieldDescriptor;

use Sulu\Component\Rest\ListBuilder\FieldDescriptor;

class SearchFieldDescriptor extends FieldDescriptor
{
    /**
     * @var string
     */
    private $fieldName;

    public function __construct(
        $fieldName,
        $name,
        $translation = null,
        $disabled = false,
        $default = false,
        $type = '',
        $width = '',
        $minWidth = '',
        $sortable = true,
        $editable = false,
        $cssClass = ''
    ) {
        parent::__construct(
            $name,
            $translation,
            $disabled,
            $default,
            $type,
            $width,
            $minWidth,
            $sortable,
            $editable,
            $cssClass
        );

        $this->fieldName = $fieldName;
    }

    /**
     * Returns field-name.
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}
