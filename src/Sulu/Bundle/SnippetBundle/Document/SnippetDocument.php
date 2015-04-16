<?php

namespace Sulu\Bundle\SnippetBundle\Document;

use Sulu\Component\DocumentManager\Behavior\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\UuidBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\Behavior\AutoNameBehavior;
use Sulu\Component\DocumentManager\Behavior\BlameBehavior;
use Sulu\Component\DocumentManager\Behavior\TimestampBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Document\Property\PropertyContainer;
use Sulu\Component\Content\Document\Behavior\StructureTypeFilingBehavior;

/**
 * Snippet document
 */
class SnippetDocument implements
    TimestampBehavior,
    BlameBehavior,
    AutoNameBehavior,
    StructureTypeFilingBehavior,
    ContentBehavior,
    WorkflowStageBehavior,
    UuidBehavior,
    PathBehavior
{
    private $created;
    private $changed;
    private $creator;
    private $changer;
    private $parent;
    private $title;
    private $workflowStage;
    private $published;
    private $uuid;
    private $structureType;
    private $content;
    private $locale;
    private $path;

    public function __construct()
    {
        $this->workflowStage = WorkflowStage::TEST;
        $this->content = new PropertyContainer();
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeName() 
    {
        return $this->nodeName;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle() 
    {
        return $this->title;
    }

    /**
     * Set the title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated() 
    {
        return $this->created;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanged() 
    {
        return $this->changed;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreator() 
    {
        return $this->creator;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanger() 
    {
        return $this->changer;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent() 
    {
        return $this->parent;
    }

    /**
     * {@inheritDoc}
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowStage() 
    {
        return $this->workflowStage;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setWorkflowStage($workflowStage)
    {
        $this->workflowStage = $workflowStage;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * {@inheritDoc}
     */
    public function getUuid() 
    {
        return $this->uuid;
    }

    /**
     * {@inheritDoc}
     */
    public function getStructureType() 
    {
        return $this->structureType;
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath() 
    {
        return $this->path;
    }
}
