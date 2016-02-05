<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type;

use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;

class ConcatenationType extends PropertyType
{
    /**
     * @var FieldMetadata[]
     */
    private $fields = [];

    /**
     * @return FieldMetadata
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function addField(FieldMetadata $field)
    {
        $this->fields[] = $field;
    }
}
