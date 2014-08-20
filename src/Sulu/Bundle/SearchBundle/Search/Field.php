<?php

namespace Sulu\Bundle\SearchBundle\Search;

class Field
{
    protected $name;
    protected $type;
    protected $value;

    const TYPE_STRING = 'string';

    public static function create($name, $value, $type = self::TYPE_STRING)
    {
        $field = new Field();
        $field->setName($name);
        $field->setValue($value);
        $field->setType($type);

        return $field;
    }

    public function getName() 
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getType() 
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
    }
    
    public function getValue() 
    {
        return $this->value;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
    }
}
