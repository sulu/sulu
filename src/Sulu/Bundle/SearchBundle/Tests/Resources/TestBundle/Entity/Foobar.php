<?php

namespace Sulu\Bundle\SuluSearchBundle\Tests\Resources\Entity;

class Foobar
{
    /**
     * @SuluSearch\Field(type="string", hints={"elastica_include_in_all": true})
     */
    protected $title;

    /**
     * @SuluSearch\Field(type="string")
     */
    protected $body;

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
}
