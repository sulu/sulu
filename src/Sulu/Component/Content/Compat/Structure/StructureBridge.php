<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * @deprecated Should be replaced by a proper StructureInterface implementation
 */
class StructureBridge implements StructureInterface
{
    /**
     * @var StructureMetadata
     */
    protected $structure;

    /**
     * @var object
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
    private $loadedProperties = [];

    /**
     * Needed by structure extensions when the document has not been set..
     *
     * @var string
     */
    protected $locale;

    /**
     * @param object $document
     */
    public function __construct(
        StructureMetadata $structure,
        DocumentInspector $inspector,
        LegacyPropertyFactory $propertyFactory,
        $document = null
    ) {
        $this->structure = $structure;
        $this->inspector = $inspector;
        $this->propertyFactory = $propertyFactory;
        $this->document = $document;
    }

    public function setDocument(StructureBehavior $document)
    {
        $this->document = $document;
    }

    public function setLanguageCode($locale)
    {
        $this->locale = $locale;
    }

    public function getLanguageCode()
    {
        if (!$this->document) {
            return $this->locale;
        }

        return $this->inspector->getLocale($this->getDocument());
    }

    public function setWebspaceKey($webspace)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getWebspaceKey()
    {
        if (!$this->document) {
            return null;
        }

        return $this->inspector->getWebspace($this->getDocument());
    }

    public function getUuid()
    {
        return $this->getDocument()->getUuid();
    }

    public function setUuid($uuid)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getCreator()
    {
        return $this->getDocument()->getCreator();
    }

    public function setCreator($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getChanger()
    {
        return $this->getDocument()->getChanger();
    }

    public function setChanger($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getCreated()
    {
        return $this->getDocument()->getCreated();
    }

    public function setCreated(\DateTime $created)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getChanged()
    {
        return $this->getDocument()->getChanged();
    }

    public function setChanged(\DateTime $changed)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getKey()
    {
        return $this->structure->getName();
    }

    public function getInternal()
    {
        return $this->structure->isInternal();
    }

    public function getProperty($name)
    {
        if ($this->hasProperty($name)) {
            $property = $this->structure->getProperty($name);
        } else {
            $property = $this->structure->getChild($name);
        }

        return $this->createLegacyPropertyFromItem($property);
    }

    public function hasProperty($name)
    {
        return $this->structure->hasProperty($name);
    }

    public function getProperties($flatten = false)
    {
        if ($flatten) {
            $items = $this->structure->getProperties();
        } else {
            $items = $this->structure->getChildren();
        }

        $propertyBridges = [];
        foreach ($items as $property) {
            $propertyBridges[$property->getName()] = $this->createLegacyPropertyFromItem($property);
        }

        return $propertyBridges;
    }

    public function getExt()
    {
        return $this->document->getExtensionsData();
    }

    public function setExt($data)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function setHasChildren($hasChildren)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getHasChildren()
    {
        return $this->inspector->hasChildren($this->getDocument());
    }

    public function setChildren($children)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getChildren()
    {
        $children = [];

        foreach ($this->getDocument()->getChildren() as $child) {
            $children[] = $this->documentToStructure($child);
        }

        return $children;
    }

    /**
     * @return $this
     */
    public function getParent()
    {
        return $this->documentToStructure($this->inspector->getParent($this->getDocument()));
    }

    public function getPublishedState()
    {
        return WorkflowStage::PUBLISHED === $this->getWorkflowDocument(__METHOD__)->getWorkflowStage();
    }

    public function setPublished($published)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getPublished()
    {
        return $this->getWorkflowDocument(__METHOD__)->getPublished();
    }

    public function getPropertyValue($name)
    {
        return $this->getProperty($name)->getValue();
    }

    public function getPropertyNames()
    {
        return \array_keys($this->structure->getChildren());
    }

    public function setType($type)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getType()
    {
        $document = $this->getDocument();
        $localizationState = $this->inspector->getLocalizationState($document);

        if (LocalizationState::GHOST === $localizationState) {
            return StructureType::getGhost($this->getDocument()->getLocale());
        }

        if (LocalizationState::SHADOW === $this->inspector->getLocalizationState($document)) {
            return StructureType::getShadow($this->getDocument()->getLocale());
        }
    }

    public function getPath()
    {
        return $this->inspector->getContentPath($this->getDocument());
    }

    public function setPath($path)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function setHasTranslation($hasTranslation)
    {
        $this->readOnlyException(__METHOD__);
    }

    public function getHasTranslation()
    {
        return $this->getTitle() ? true : false;
    }

    public function toArray($complete = true)
    {
        $document = $this->getDocument();

        $result = [
            'id' => $this->inspector->getUuid($document),
            'path' => $this->inspector->getContentPath($document),
            'nodeType' => $this->getNodeType(),
            'nodeState' => $this->getNodeState(),
            'internal' => false,
            'availableLocales' => $this->inspector->getLocales($document),
            'contentLocales' => $this->inspector->getConcreteLocales($document),
            'hasSub' => $this->getHasChildren(),
            'title' => $document->getTitle(), // legacy system returns diffent fields for title depending on $complete
        ];

        if ($document instanceof OrderBehavior) {
            $result['order'] = $document->getSuluOrder();
        }

        if ($document instanceof RedirectTypeBehavior) {
            $redirectType = $document->getRedirectType();
            $result['linked'] = null;
            if (RedirectType::INTERNAL == $redirectType && null !== $document->getRedirectTarget()) {
                $result['linked'] = 'internal';
                $result['internal_link'] = $document->getRedirectTarget()->getUuid();
            } elseif (RedirectType::EXTERNAL == $redirectType) {
                $result['linked'] = 'external';
                $result['external'] = $document->getRedirectExternal();
            }
        }

        if ($document instanceof WorkflowStageBehavior) {
            $result['publishedState'] = WorkflowStage::PUBLISHED === $document->getWorkflowStage();
            $result['published'] = $document->getPublished();
        }

        $result['navContexts'] = [];
        if ($document instanceof NavigationContextBehavior) {
            $result['navContexts'] = $document->getNavigationContexts();
        }

        if (null !== $this->getType()) {
            $result['type'] = $this->getType()->toArray();
        }

        if ($complete) {
            if ($document instanceof ShadowLocaleBehavior) {
                $result = \array_merge(
                    $result,
                    [
                        'shadowLocales' => $this->inspector->getShadowLocales($document),
                        'shadowOn' => $document->isShadowLocaleEnabled(),
                        'shadowBaseLanguage' => $document->getShadowLocale(),
                    ]
                );
            }

            $result = \array_merge(
                $result,
                [
                    'template' => $this->structure->getName(),
                    'originTemplate' => $this->structure->getName(),
                    'creator' => $document->getCreator(),
                    'changer' => $document->getChanger(),
                    'created' => $document->getCreated(),
                    'changed' => $document->getChanged(),
                    'title' => $document->getTitle(),
                    'url' => null,
                ]
            );

            if ($document instanceof ResourceSegmentBehavior) {
                $result['url'] = $document->getResourceSegment();
            }

            if ($document instanceof ExtensionBehavior) {
                $result['ext'] = $document->getExtensionsData();
            }

            $result = \array_merge($this->getDocument()->getStructure()->toArray(), $result);

            return $result;
        }

        return $result;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
    }

    public function getPropertyByTagName($tagName, $highest = true)
    {
        return $this->createLegacyPropertyFromItem($this->structure->getPropertyByTagName($tagName, $highest));
    }

    public function getPropertiesByTagName($tagName)
    {
        $properties = [];
        foreach ($this->structure->getPropertiesByTagName($tagName) as $structureProperty) {
            $properties[] = $this->createLegacyPropertyFromItem($structureProperty);
        }

        return $properties;
    }

    public function getPropertyValueByTagName($tagName)
    {
        return $this->getPropertyByTagName($tagName)->getValue();
    }

    public function hasTag($tag)
    {
        return $this->structure->hasPropertyWithTagName($tag);
    }

    public function getNodeType()
    {
        if ($this->getDocument() instanceof RedirectTypeBehavior) {
            return $this->getDocument()->getRedirectType();
        }

        return RedirectType::NONE;
    }

    public function getNodeName()
    {
        if ($this->document instanceof RedirectTypeBehavior
            && RedirectType::INTERNAL == $this->document->getRedirectType()
            && null !== $this->document->getRedirectTarget()
        ) {
            return $this->getDocument()->getRedirectTarget()->getTitle();
        }

        return $this->getDocument()->getTitle();
    }

    public function getLocalizedTitle($languageCode)
    {
        return $this->structure->getTitle($languageCode);
    }

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

    public function copyFrom(StructureInterface $structure)
    {
        foreach ($this->getProperties(true) as $property) {
            if ($structure->hasProperty($property->getName())) {
                $property->setValue($structure->getPropertyValue($property->getName()));
            }
        }

        $this->setDocument($structure->getDocument());
    }

    /**
     * Magic getter.
     *
     * @deprecated Do not use magic getters. Use ArrayAccess instead
     */
    public function __get($name)
    {
        return $this->getProperty($name)->getValue();
    }

    public function getShadowLocales()
    {
        return $this->inspector->getShadowLocales($this->getDocument());
    }

    public function getContentLocales()
    {
        return $this->inspector->getConcreteLocales($this->getDocument());
    }

    public function getIsShadow()
    {
        if (!$this->document) {
            return false;
        }

        $document = $this->getDocument();
        if (!$document instanceof ShadowLocaleBehavior) {
            return false;
        }

        return $document->isShadowLocaleEnabled();
    }

    public function getShadowBaseLanguage()
    {
        $document = $this->getDocument();
        if (!$document instanceof ShadowLocaleBehavior) {
            return;
        }

        return $document->getShadowLocale();
    }

    public function getResourceLocator()
    {
        $document = $this->getDocument();
        if (RedirectType::EXTERNAL == $document->getRedirectType()) {
            return $document->getRedirectExternal();
        }

        if (RedirectType::INTERNAL === $document->getRedirectType()) {
            $target = $document->getRedirectTarget();

            if (!$target) {
                throw new \RuntimeException('Document is an internal redirect, but no redirect target has been set.');
            }

            return $target->getResourceSegment();
        }

        return $document->getResourceSegment();
    }

    /**
     * Returns document.
     *
     * @return object
     */
    public function getDocument()
    {
        if (!$this->document) {
            throw new \RuntimeException(
                'Document has not been applied to structure yet, cannot retrieve data from structure.'
            );
        }

        return $this->document;
    }

    /**
     * Returns structure metadata.
     *
     * @return StructureMetadata
     */
    public function getStructure()
    {
        return $this->structure;
    }

    protected function readOnlyException($method)
    {
        throw new \BadMethodCallException(
            \sprintf(
                'Compatibility layer StructureBridge instances are readonly. Tried to call "%s"',
                $method
            )
        );
    }

    /**
     * @param StructureBehavior $document The document to convert
     *
     * @return $this
     */
    protected function documentToStructure(StructureBehavior $document)
    {
        return new $this(
            $this->inspector->getStructureMetadata($document),
            $this->inspector,
            $this->propertyFactory,
            $document
        );
    }

    private function getWorkflowDocument($method)
    {
        $document = $this->getDocument();
        if (!$document instanceof WorkflowStageBehavior) {
            throw new \BadMethodCallException(
                \sprintf(
                    'Cannot call "%s" on Document which does not implement PageInterface. Is "%s"',
                    $method,
                    \get_class($document)
                )
            );
        }

        return $document;
    }

    private function notImplemented($method)
    {
        throw new \InvalidArgumentException(
            \sprintf(
                'Method "%s" is not yet implemented',
                $method
            )
        );
    }

    private function normalizeData(?array $data = null)
    {
        if (null === $data) {
            return;
        }

        if (false === \is_array($data)) {
            return $this->normalizeValue($data);
        }

        foreach ($data as &$value) {
            if (\is_array($value)) {
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
        if ($value instanceof StructureBehavior) {
            return $this->documentToStructure($value);
        }

        return $value;
    }

    private function createLegacyPropertyFromItem($item)
    {
        $name = $item->getName();
        if (isset($this->loadedProperties[$name])) {
            return $this->loadedProperties[$name];
        }

        $propertyBridge = $this->propertyFactory->createProperty($item, $this);

        if ($this->document) {
            $property = $this->getDocument()->getStructure()->getProperty($name);
            $propertyBridge->setPropertyValue($property);
        }

        $this->loadedProperties[$name] = $propertyBridge;

        return $propertyBridge;
    }
}
