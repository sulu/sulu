<?php

namespace Sulu\Component\Content\Document\Behavior;

interface ContentBehavior
{
    public function getTemplate() 
    {
        return $this->template;
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getContent()
    {
    }
}
