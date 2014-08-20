<?php

namespace Sulu\Bundle\SearchBundle\Search\Metadata;

use Metadata\ClassMetadata;

class IndexMetadata extends ClassMetadata
{
    protected $indexName;

    protected $fieldMapping = array();

    public function getIndexName() 
    {
        return $this->indexName;
    }
    
    public function setIndexName($indexName)
    {
        $this->indexName = $indexName;
    }

    public function getFieldMapping() 
    {
        return $this->fieldMapping;
    }
    
    public function setFieldMapping($fieldMapping)
    {
        $this->fieldMapping = $fieldMapping;
    }

    public function addFieldMapping($name, $mapping)
    {
        $this->fieldMapping[$name] = $mapping;
    }
}
