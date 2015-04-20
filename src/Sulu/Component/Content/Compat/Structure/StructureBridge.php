<?php

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\Structure as LegacyStructure;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Section\SectionProperty;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\StructureType;

use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\ContentDocumentInterface;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Structure\Factory\StructureFactory;
use Sulu\Component\Content\Structure\Item;
use Sulu\Component\Content\Structure\Property as NewProperty;
use Sulu\Component\Content\Structure\Section;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\Content\Structure\Block;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;

class StructureBridge implements StructureInterface
{
    /**
     * @var Structure
     */
    protected $structure;

    /**
     * @var Document
     */
    protected $document;

    /**
     * @var DocumentInspector
     */
    protected $inspector;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    /**
     * @var array
     */
    private $loadedProperties = array();

    /**
     * Needed by structure extensions when the document has not been set..
     *
     * @var string
     */
    private $locale;

    /**
     * @param Structure         $structure
     * @param object $document
     * @param PageUrlGenerator  $urlGenerator
     */
    public function __construct(
        Structure $structure,
        DocumentInspector $inspector,
        LegacyPropertyFactory $propertyFactory,
        $document = null
    ) {
        $this->structure = $structure;
        $this->inspector = $inspector;
        $this->document = $document;
        $this->propertyFactory = $propertyFactory;
    }

    /**
     * @param ContentDocumentInterface $document
     */
    public function setDocument(ContentBehavior $document)
    {
        $this->document = $document;
    }

    /**
     * {@inheritDoc}
     */
    public function setLanguageCode($locale)
    {
        $this->locale = $locale;
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguageCode()
    {
        if (!$this->document) {
            return $this->locale;
        }

        return $this->inspector->getOriginalLocale($this->document);
    }

    /**
     * {@inheritDoc}
     */
    public function setWebspaceKey($webspace)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getWebspaceKey()
    {
        return $this->inspector->getWebspace($this->document);
    }

    /**
     * {@inheritDoc}
     */
    public function getUuid()
    {
        return $this->getDocument()->getUuid();
    }

    /**
     * {@inheritDoc}
     */
    public function setUuid($uuid)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreator()
    {
        return $this->getDocument()->getCreator();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreator($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getChanger()
    {
        return $this->getDocument()->getChanger();
    }

    /**
     * {@inheritDoc}
     */
    public function setChanger($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated()
    {
        return $this->getDocument()->getCreated();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreated(\DateTime $created)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getChanged()
    {
        return $this->getDocument()->getChanged();
    }

    /**
     * {@inheritDoc}
     */
    public function setChanged(\DateTime $changed)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return $this->structure->getName();
    }

    /**
     * TODO: Implement this
     */
    public function getInternal()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name)
    {
        if (isset($this->loadedProperties[$name])) {
            return $this->loadedProperties[$name];
        }

        $property = $this->structure->getChild($name);

        return $this->createLegacyPropertyFromItem($property);
    }

    private function createLegacyPropertyFromItem($item)
    {
        $propertyBridge = $this->propertyFactory->createProperty($item);
        $name = $item->getName();

        if ($this->document) {
            $property = $this->document->getContent()->getProperty($name);

            if ($item instanceof Block) {
                $propertyBridge->setValue($property->getValue());
            } else {
                $propertyBridge->setPropertyValue($property);
            }

        }

        $propertyBridge->setStructure($this);
        $this->loadedProperties[$name] = $propertyBridge;

        return $propertyBridge;
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name)
    {
        return $this->structure->hasChild($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties($flatten = false)
    {
        if ($flatten) {
            $items = $this->structure->getProperties();
        } else {
            $items = $this->structure->getChildren();
        }

        $propertyBridges = array();
        foreach ($items as $propertyName => $property) {
            $propertyBridges[$propertyName] = $this->createLegacyPropertyFromItem($property);
        }

        return $propertyBridges;
    }

    /**
     * {@inheritDoc}
     */
    public function setHasChildren($hasChildren)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getHasChildren()
    {
        return $this->getDocument()->getChildren()->count() ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function setChildren($children)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        $children = array();

        foreach ($this->inspector->getChildren($this->getDocument()) as $child) {
            $children[] = $this->documentToStructure($child);
        }

        return $children;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublishedState()
    {
        return $this->getWorkflowDocument(__METHOD__)->getWorkflowStage() === WorkflowStage::PUBLISHED;
    }

    /**
     * {@inheritDoc}
     */
    public function setPublished($published)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublished()
    {
        return $this->getWorkflowDocument(__METHOD__)->getPublished();
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyValue($name)
    {
        return $this->getProperty($name)->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyNames()
    {
        return array_keys($this->structure->children);
    }

    /**
     * {@inheritDoc}
     */
    public function setType($type)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        $document = $this->getDocument();
        $localizationState = $this->inspector->getLocalizationState($document);

        if ($localizationState === LocalizationState::GHOST) {
            return StructureType::getGhost($this->getDocument()->getLocale());
        }

        if ($this->inspector->getLocalizationState($document) === LocalizationState::SHADOW) {
            return StructureType::getShadow($this->getDocument()->getLocale());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->inspector->getContentPath($this->getDocument());
    }

    /**
     * {@inheritDoc}
     */
    public function setPath($path)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function setHasTranslation($hasTranslation)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getHasTranslation()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray($complete = true)
    {
        $document = $this->getDocument();

        $result = array(
            'id' => $this->inspector->getUuid($document),
            'path' => $this->inspector->getContentPath($document),
            'nodeType' => $this->getNodeType(),
            'nodeState' => $this->getNodeState(),
            'internal' => false,
            'concreteLanguages' => $this->inspector->getLocales($document),
            'hasSub' => $this->inspector->getChildren($document)->count() ? true : false,
            'title' => $document->getTitle(), // legacy system returns diffent fields for title depending on $complete
        );

        if ($document instanceof RedirectTypeBehavior) {
            $redirectType = $document->getRedirectType();
            $result['linked'] = null;
            if ($redirectType == RedirectType::INTERNAL) {
                $result['linked'] = 'internal';
            } elseif ($redirectType == RedirectType::EXTERNAL) {
                $result['linked'] = 'external';
            }
        }

        if ($document instanceof WorkflowStageBehavior) {
            $result['publishedState'] = $document->getWorkflowStage() === WorkflowStage::PUBLISHED;
            $result['published'] = $document->getPublished();
        }

        $result['navContexts'] = array();
        if ($document instanceof NavigationContextBehavior) {
            $result['navContexts'] = $document->getNavigationContexts();
        }

        if ($complete) {
            if ($document instanceof ShadowLocaleBehavior) {
                $result = array_merge($result, array(
                    'enabledShadowLanguages' => $this->inspector->getShadowLocales($document),
                    'shadowOn' => $document->isShadowLocaleEnabled(),
                    'shadowBaseLanguage' => $document->getShadowLocale() ?: false,
                ));
            }

            $result = array_merge($result, array(
                'template' => $this->structure->getName(),
                'originTemplate' => $this->structure->getName(),
                'creator' => $document->getCreator(),
                'changer' => $document->getChanger(),
                'created' => $document->getCreated(),
                'changed' => $document->getChanged(),
                'title' => $document->getTitle(),
                'url' => $document->getResourceSegment(),
            ));

            if ($document instanceof ExtensionBehavior) {
                $result['ext'] = $document->getExtensionsData();
            }

            $result = array_merge($this->getDocument()->getContent()->toArray(), $result);

            return $result;
        }

        if (null !== $this->getType()) {
            $result['type'] = $this->getType()->toArray();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        return $this->createLegacyPropertyFromItem($this->structure->getPropertyByTagName($tagName, $highest));
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertiesByTagName($tagName)
    {
        $properties = array();
        foreach ($this->structure->getPropertiesByTagName($tagName) as $structureProperty) {
            $properties[] = $this->createLegacyPropertyFromItem($structureProperty);
        }

        return $properties;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyValueByTagName($tagName)
    {
        return $this->getPropertyByTagName($tagName)->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function hasTag($tag)
    {
        return $this->structure->hasPropertyWithTagName($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeType()
    {
        if ($this->getDocument() instanceof RedirectTypeBehavior) {
            return $this->document->getRedirectType();
        }

        return RedirectType::NONE;
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeName()
    {
        if ($this->getDocument()->getRedirectType() == RedirectType::INTERNAL) {
            return $this->inspector->getName($this->document->getRedirectTarget());
        }

        if ($this->getDocument()->getRedirectType() == RedirectType::EXTERNAL) {
            return $this->document->getTitle();
        }

        return $this->inspector->getName($this->getDocument());
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalizedTitle($languageCode)
    {
        return $this->structure->getLocalizedTitle($languageCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeState()
    {
        $document = $this->getDocument();

        if (!$document instanceof WorkflowStageBehavior) {
            return WorkflowStage::PUBLISHED;
        }

        return $this->getDocument()->getWorkflowStage();
    }

    public function getTitle()
    {
        return $this->getDocument()->getTitle();
    }

    public function getUrl()
    {
        return $this->getDocument()->getResourceSegment();
    }

    /**
     * {@inheritDoc}
     */
    public function copyFrom(StructureInterface $structure)
    {
        $this->notImplemented(__METHOD__);
    }

    /**
     * Magic getter
     *
     * @deprecated Do not use magic getters. Use ArrayAccess instead.
     */
    public function __get($name)
    {
        return $this->getProperty($name)->getValue();
    }

    public function getEnabledShadowLanguages()
    {
        return $this->inspector->getShadowLocales($this->getDocument());
    }

    public function getConcreteLanguages()
    {
        return $this->inspector->getConcreteLocales($this->getDocument());
    }

    public function getIsShadow()
    {
        $document = $this->getDocument();
        return $document->isShadowLocaleEnabled();
    }

    public function getShadowBaseLanguage()
    {
        return $this->getDocument()->getShadowLocale();
    }

    public function getResourceLocator()
    {
        $document = $this->getDocument();
        if ($document->getRedirectType() == RedirectType::EXTERNAL) {
            return 'http://' . $document->getRedirectExternal();
        }

        if ($document->getRedirectType() === RedirectType::INTERNAL) {
            return $document->getRedirectTarget()->getResourceSegment();
        }

        return $document->getResourceSegment();
    }

    protected function readOnlyException($method)
    {
        throw new \BadMethodCallException(sprintf(
            'Compatibility layer StructureBridge instances are readonly. Tried to call "%s"',
            $method
        ));
    }

    protected function getDocument()
    {
        if (!$this->document) {
            throw new \RuntimeException(
                'Document has not been applied to structure yet, cannot retrieve data from structure.'
            );
        }

        return $this->document;
    }

    private function getWorkflowDocument($method)
    {
        $document = $this->getDocument();
        if (!$document instanceof WorkflowStageBehavior) {
            throw new \BadMethodCallException(sprintf(
                'Cannot call "%s" on Document which does not implement PageInterface. Is "%s"',
                $method, get_class($document)
            ));
        }

        return $document;
    }

    private function notImplemented($method)
    {
        throw new \InvalidArgumentException(sprintf(
            'Method "%s" is not yet implemented', $method
        ));
    }

    private function normalizeData(array $data = null)
    {
        if (null === $data) {
            return;
        }

        if (false === is_array($data)) {
            return $this->normalizeValue($data);
        }

        foreach ($data as &$value) {
            if (is_array($value)) {
                foreach ($value as $childKey => $childValue) {
                    $data[$childKey] = $this->normalizeData($childValue);
                }
            }

            $value = $this->normalizeValue($value);
        }

        return $data;
    }

    private function normalizeValue($value)
    {
        if ($value instanceof ContentDocumentInterface) {
            return $this->documentToStructure($value);
        }

        return $value;
    }

    protected function documentToStructure(ContentBehavior $document)
    {
        return new $this($this->inspector->getStructure($document), $this->inspector, $this->propertyFactory, $document);
    }
}
