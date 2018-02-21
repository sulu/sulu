<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid;

use JMS\Serializer\Annotation as Serializer;

class Datagrid
{
    /**
     * @var Field[]
     *
     * @Serializer\Inline()
     */
    protected $fields;

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields($fields): void
    {
        $this->fields = $fields;
    }

    public function addField(Field $field): void
    {
        $this->fields[$field->getName()] = $field;
    }
}
