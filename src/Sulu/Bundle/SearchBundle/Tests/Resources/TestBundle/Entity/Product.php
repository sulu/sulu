<?php

namespace Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity;

class Product
{
    protected $title;

    protected $body;

    protected $date;

    public function getTitle() 
    {
        return $this->title;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getBody() 
    {
        return $this->body;
    }
    
    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getDate() 
    {
        return $this->date;
    }
    
    public function setDate($date)
    {
        $this->date = $date;
    }
    
}
