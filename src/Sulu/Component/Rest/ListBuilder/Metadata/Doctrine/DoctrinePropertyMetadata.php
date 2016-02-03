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
     * @var DoctrineJoinMetadata[]
     */
    private $joins = [];

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

    /**
     * @return DoctrineJoinMetadata[]
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @param DoctrineJoinMetadata[] $joins
     */
    public function setJoins(array $joins)
    {
        $this->joins = $joins;
    }

    /**
     * @param DoctrineJoinMetadata $join
     */
    public function addJoin(DoctrineJoinMetadata $join)
    {
        $this->joins[] = $join;
    }
}
