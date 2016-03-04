<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Document;

use Sulu\Component\Content\Document\Behavior\AuditableBehavior;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedStructureBehavior;
use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\Content\Document\Structure\StructureInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;
use Sulu\Component\Content\Document\Behavior\SyncronizeBehavior;

/**
 * Base document for Page-like documents (i.e. Page and Home documents).
 */
class BasePageDocument implements
    NodeNameBehavior,
    ParentBehavior,
    LocalizedStructureBehavior,
    ResourceSegmentBehavior,
    NavigationContextBehavior,
    RedirectTypeBehavior,
    WorkflowStageBehavior,
    ShadowLocaleBehavior,
    UuidBehavior,
    ChildrenBehavior,
    PathBehavior,
    ExtensionBehavior,
    OrderBehavior,
    WebspaceBehavior,
    SecurityBehavior,
    AuditableBehavior,
    SyncronizeBehavior
{
    /**
     * The name of this node.
     *
     * @var string
     */
    protected $nodeName;

    /**
     * Datetime of create document.
     *
     * @var \DateTime
     */
    protected $created;

    /**
     * Changed date of page.
     *
     * @var \DateTime
     */
    protected $changed;

    /**
     * User ID of creator.
     *
     * @var int
     */
    protected $creator;

    /**
     * User ID of changer.
     *
     * @var int
     */
    protected $changer;

    /**
     * Document's parent.
     *
     * @var object
     */
    protected $parent;

    /**
     * Title of document.
     *
     * @var string
     */
    protected $title;

    /**
     * Segment.
     *
     * @var string
     */
    protected $resourceSegment;

    /**
     * @var string[]
     */
    protected $navigationContexts = [];

    /**
     * Type of redirection.
     *
     * @var int
     */
    protected $redirectType;

    /**
     * The target of redirection.
     *
     * @var object
     */
    protected $redirectTarget;

    /**
     * The External redirect.
     *
     * @var string
     */
    protected $redirectExternal;

    /**
     * Workflow Stage currently Test or Published.
     *
     * @var int
     */
    protected $workflowStage;

    /**
     * Is Document is published.
     *
     * @var bool
     */
    protected $published;

    /**
     * Shadow locale is enabled.
     *
     * @var bool
     */
    protected $shadowLocaleEnabled = false;

    /**
     * Shadow locale.
     *
     * @var string
     */
    protected $shadowLocale;

    /**
     * Universal Identifier.
     *
     * @var string
     */
    protected $uuid;

    /**
     * Document's type of structure ie default, complex...
     *
     * @var string
     */
    protected $structureType;

    /**
     * Structure.
     *
     * @var StructureInterface
     */
    protected $structure;

    /**
     * Document's locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * Document's children.
     *
     * @var ChildrenCollection
     */
    protected $children;

    /**
     * Path of Document.
     *
     * @var string
     */
    protected $path;

    /**
     * Document's extensions ie seo, ...
     *
     * @var ExtensionContainer
     */
    protected $extensions;

    /**
     * Document's webspace name.
     *
     * @var string
     */
    protected $webspaceName;

    /**
     * Document's order.
     *
     * @var int
     */
    protected $suluOrder;

    /**
     * List of permissions.
     *
     * @var array
     */
    protected $permissions;

    /**
     * Names of all the non-default document managers to which this document
     * is syncronized with.
     */
    protected $syncronizedManagers;

    public function __construct()
    {
        $this->workflowStage = WorkflowStage::TEST;
        $this->redirectType = RedirectType::NONE;
        $this->structure = new Structure();
        $this->extensions = new ExtensionContainer();
        $this->children = new \ArrayIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceSegment()
    {
        return $this->resourceSegment;
    }

    /**
     * {@inheritdoc}
     */
    public function setResourceSegment($resourceSegment)
    {
        $this->resourceSegment = $resourceSegment;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationContexts()
    {
        return $this->navigationContexts;
    }

    /**
     * {@inheritdoc}
     */
    public function setNavigationContexts(array $navigationContexts = [])
    {
        $this->navigationContexts = $navigationContexts;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectType()
    {
        return $this->redirectType;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectType($redirectType)
    {
        $this->redirectType = $redirectType;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectTarget()
    {
        return $this->redirectTarget;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectTarget($redirectTarget)
    {
        $this->redirectTarget = $redirectTarget;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectExternal()
    {
        return $this->redirectExternal;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectExternal($redirectExternal)
    {
        $this->redirectExternal = $redirectExternal;
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflowStage()
    {
        return $this->workflowStage;
    }

    /**
     * {@inheritdoc}
     */
    public function setWorkflowStage($workflowStage)
    {
        $this->workflowStage = $workflowStage;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * {@inheritdoc}
     */
    public function getShadowLocale()
    {
        return $this->shadowLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function setShadowLocale($shadowLocale)
    {
        $this->shadowLocale = $shadowLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function isShadowLocaleEnabled()
    {
        return $this->shadowLocaleEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function setShadowLocaleEnabled($shadowLocaleEnabled)
    {
        $this->shadowLocaleEnabled = $shadowLocaleEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructureType()
    {
        return $this->structureType;
    }

    /**
     * {@inheritdoc}
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * {@inheritdoc}
     */
    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionsData()
    {
        return $this->extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionsData($extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtension($name, $data)
    {
        $this->extensions[$name] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspaceName()
    {
        return $this->webspaceName;
    }

    /**
     * {@inheritdoc}
     */
    public function getSuluOrder()
    {
        return $this->suluOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getSyncronizedManagers()
    {
        return $this->syncronizedManagers;
    }
}
