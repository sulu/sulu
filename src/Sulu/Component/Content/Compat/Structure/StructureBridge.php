<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @param StructureMetadata $structure
     * @param DocumentInspector $inspector
     * @param LegacyPropertyFactory $propertyFactory
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

    /**
     * @param StructureBehavior $document
     */
    public function setDocument(StructureBehavior $document)
    {
        $this->document = $document;
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguageCode($locale)
    {
        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageCode()
    {
        if (!$this->document) {
            return $this->locale;
        }

        return $this->inspector->getLocale($this->getDocument());
    }

    /**
     * {@inheritdoc}
     */
    public function setWebspaceKey($webspace)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspaceKey()
    {
        return $this->inspector->getWebspace($this->getDocument());
    }

    /**
     * {@inheritdoc}
     */
    public function getUuid()
    {
        return $this->getDocument()->getUuid();
    }

    /**
     * {@inheritdoc}
     */
    public function setUuid($uuid)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->getDocument()->getCreator();
    }

    /**
     * {@inheritdoc}
     */
    public function setCreator($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getChanger()
    {
        return $this->getDocument()->getChanger();
    }

    /**
     * {@inheritdoc}
     */
    public function setChanger($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->getDocument()->getCreated();
    }

    /**
     * {@inheritdoc}
     */
    public function setCreated(\DateTime $created)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged()
    {
        return $this->getDocument()->getChanged();
    }

    /**
     * {@inheritdoc}
     */
    public function setChanged(\DateTime $changed)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->structure->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getInternal()
    {
        return $this->structure->isInternal();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($name)
    {
        if ($this->hasProperty($name)) {
            $property = $this->structure->getProperty($name);
        } else {
            $property = $this->structure->getChild($name);
        }

        return $this->createLegacyPropertyFromItem($property);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($name)
    {
        return $this->structure->hasProperty($name);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function setHasChildren($hasChildren)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getHasChildren()
    {
        return $this->inspector->hasChildren($this->getDocument());
    }

    /**
     * {@inheritdoc}
     */
    public function setChildren($children)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getPublishedState()
    {
        return $this->getWorkflowDocument(__METHOD__)->getWorkflowStage() === WorkflowStage::PUBLISHED;
    }

    /**
     * {@inheritdoc}
     */
    public function setPublished($published)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getPublished()
    {
        return $this->getWorkflowDocument(__METHOD__)->getPublished();
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValue($name)
    {
        return $this->getProperty($name)->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyNames()
    {
        return array_keys($this->structure->children);
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->inspector->getContentPath($this->getDocument());
    }

    /**
     * {@inheritdoc}
     */
    public function setPath($path)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function setHasTranslation($hasTranslation)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritdoc}
     */
    public function getHasTranslation()
    {
        return $this->getTitle() ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($complete = true)
    {
        $document = $this->getDocument();

        $result = [
            'id' => $this->inspector->getUuid($document),
            'path' => $this->inspector->getContentPath($document),
            'nodeType' => $this->getNodeType(),
            'nodeState' => $this->getNodeState(),
            'internal' => false,
            'concreteLanguages' => $this->inspector->getLocales($document),
            'hasSub' => $this->getHasChildren(),
            'title' => $document->getTitle(), // legacy system returns diffent fields for title depending on $complete
        ];

        if ($document instanceof OrderBehavior) {
            $result['order'] = $document->getSuluOrder();
        }

        if ($document instanceof RedirectTypeBehavior) {
            $redirectType = $document->getRedirectType();
            $result['linked'] = null;
            if ($redirectType == RedirectType::INTERNAL && $document->getRedirectTarget() !== null) {
                $result['linked'] = 'internal';
                $result['internal_link'] = $document->getRedirectTarget()->getUuid();
            } elseif ($redirectType == RedirectType::EXTERNAL) {
                $result['linked'] = 'external';
                $result['external'] = $document->getRedirectExternal();
            }
        }

        if ($document instanceof WorkflowStageBehavior) {
            $result['publishedState'] = $document->getWorkflowStage() === WorkflowStage::PUBLISHED;
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
                $result = array_merge(
                    $result,
                    [
                        'enabledShadowLanguages' => $this->inspector->getShadowLocales($document),
                        'shadowOn' => $document->isShadowLocaleEnabled(),
                        'shadowBaseLanguage' => $document->getShadowLocale(),
                    ]
                );
            }

            $result = array_merge(
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

            $result = array_merge($this->getDocument()->getStructure()->toArray(), $result);

            return $result;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        return $this->createLegacyPropertyFromItem($this->structure->getPropertyByTagName($tagName, $highest));
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertiesByTagName($tagName)
    {
        $properties = [];
        foreach ($this->structure->getPropertiesByTagName($tagName) as $structureProperty) {
            $properties[] = $this->createLegacyPropertyFromItem($structureProperty);
        }

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValueByTagName($tagName)
    {
        return $this->getPropertyByTagName($tagName)->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function hasTag($tag)
    {
        return $this->structure->hasPropertyWithTagName($tag);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeType()
    {
        if ($this->getDocument() instanceof RedirectTypeBehavior) {
            return $this->getDocument()->getRedirectType();
        }

        return RedirectType::NONE;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeName()
    {
        if ($this->document instanceof RedirectTypeBehavior &&
            $this->document->getRedirectType() == RedirectType::INTERNAL &&
            $this->document->getRedirectTarget() !== null
        ) {
            return $this->getDocument()->getRedirectTarget()->getTitle();
        }

        return $this->getDocument()->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalizedTitle($languageCode)
    {
        return $this->structure->getTitle($languageCode);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
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
        if ($document->getRedirectType() == RedirectType::EXTERNAL) {
            return $document->getRedirectExternal();
        }

        if ($document->getRedirectType() === RedirectType::INTERNAL) {
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
            sprintf(
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
                sprintf(
                    'Cannot call "%s" on Document which does not implement PageInterface. Is "%s"',
                    $method,
                    get_class($document)
                )
            );
        }

        return $document;
    }

    private function notImplemented($method)
    {
        throw new \InvalidArgumentException(
            sprintf(
                'Method "%s" is not yet implemented',
                $method
            )
        );
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
