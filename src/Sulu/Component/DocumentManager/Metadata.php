<?php

namespace Sulu\Component\DocumentManager;

class Metadata
{
    private $class;
    private $alias;
    private $phpcrType;

    public function getClass() 
    {
        return $this->class;
    }
    
    public function setClass($class)
    {
        $this->class = $class;
    }

    public function getAlias() 
    {
        return $this->alias;
    }
    
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getPhpcrType() 
    {
        return $this->phpcrType;
    }
    
    public function setPhpcrType($phpcrType)
    {
        $this->phpcrType = $phpcrType;
    }
}
