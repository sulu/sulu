<?php

namespace Sulu\Bundle\SearchBundle\Search;

class QueryHit
{
    protected $document;
    protected $score;
    protected $id;

    public function getDocument() 
    {
        return $this->document;
    }
    
    public function setDocument($document)
    {
        $this->document = $document;
    }
    
    public function getScore() 
    {
        return $this->score;
    }
    
    public function setScore($score)
    {
        $this->score = $score;
    }

    public function getId() 
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
}
