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
     * @var string
     */
    private $glue;

    public function __construct($glue = ' ')
    {
        $this->glue = $glue;
    }

    /**
     * @return string
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * @return FieldMetadata[]
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
