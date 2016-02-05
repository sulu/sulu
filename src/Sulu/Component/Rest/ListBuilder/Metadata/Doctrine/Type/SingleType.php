<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type;

use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;

class SingleType extends PropertyType
{
    /**
     * @var FieldMetadata
     */
    private $field;

    public function __construct(FieldMetadata $field)
    {
        $this->field = $field;
    }

    /**
     * @return FieldMetadata
     */
    public function getField()
    {
        return $this->field;
    }
}
