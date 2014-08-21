<?php

namespace Sulu\Bundle\SearchBundle\Search;

class Document
{
    protected $fields;
    protected $id;

    public function getId() 
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function addField(Field $field)
    {
        $this->fields[] = $field;
    }

    public function getFields()
    {
        return $this->fields;
    }
}
