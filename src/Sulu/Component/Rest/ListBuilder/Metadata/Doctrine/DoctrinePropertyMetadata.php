<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine;

use Metadata\PropertyMetadata;

class DoctrinePropertyMetadata extends PropertyMetadata
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }
}
