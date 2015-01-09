<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\StructureBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * Base Structure class.
 *
 * Page and Snippet Documents will extend this class.
 *
 * @PHPCR\Document(
 *     translator="property"
 *     mixins=[sulu:structure]
 * )
 * @PHPCR\MappedSuperclass()
 */
class Structure
{
    /**
     * @PHPCR\NodeName()
     */
    protected $name;

    /**
     * @PHPCR\ParentDocument()
     */
    protected $parent;

    /**
     * @PHPCR\Children()
     */
    protected $children;

    /**
     * @PHPCR\String(translated=true, property="sulu:title")
     */
    protected $title;

    /**
     * @PHPCR\String(property="sulu:template")
     */
    protected $template;

    /**
     * @PHPCR\Long(property="sulu:creator")
     */
    protected $creator;

    /**
     * @PHPCR\Long(property="sulu:changer")
     */
    protected $changer;

    /**
     * @PHPCR\Date(property="sulu:created")
     */
    protected $created;

    /**
     * @PHPCR\Date(property="sulu:updated")
     */
    protected $updated;

    /**
     * @PHPCR\Node()
     */
    protected $node;

    public function getParent() 
    {
        return $this->parent;
    }
    
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getChildren() 
    {
        return $this->children;
    }
    
    public function setChildren($children)
    {
        $this->children = $children;
    }

    public function getTitle() 
    {
        return $this->title;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTemplate() 
    {
        return $this->template;
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getCreator() 
    {
        return $this->creator;
    }
    
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }
    
    public function getChanger() 
    {
        return $this->changer;
    }
    
    public function setChanger($changer)
    {
        $this->changer = $changer;
    }

    public function getCreated() 
    {
        return $this->created;
    }
    
    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getUpdated() 
    {
        return $this->updated;
    }
    
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    public function getContent()
    {
        $res = array();
        foreach ($this->node->getProperties('cont:*') as $name => $property) {
            $res[$name] = $property->getValue();
        }

        return $res;
    }

    public function getContent($key)
    {
        
    }

    public function getTranslatedContentValue($key)
    {
    }
}
