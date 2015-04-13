<?php

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Structure as LegacyStructure;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Section\SectionProperty;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\StructureType;

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
    private $inspector;

    /**
     * @param Structure         $structure
     * @param object $document
     * @param PageUrlGenerator  $urlGenerator
     */
    public function __construct(
        Structure $structure,
        DocumentInspector $inspector,
        $document = null
    ) {
        $this->structure = $structure;
        $this->structureFactory = $structureFactory;
        $this->document = $document;
    }

    /**
     * @param ContentDocumentInterface $document
     */
    public function setDocument(ContentDocumentInterface $document)
    {
        $this->document = $document;
    }

    /**
     * {@inheritDoc}
     */
    public function setLanguageCode($language)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguageCode()
    {
        return $this->getDocument()->getLocale();
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
        return $this->getDocument()->getWebspaceKey();
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
        $property = $this->structure->getChild($name);
        $propertyBridge = $this->createBridgeFromItem($name, $property);

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
            $propertyBridges[$propertyName] = $this->createBridgeFromItem($propertyName, $property);
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
        return $this->getDocument()->hasChildren();
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
        return $this->getWorkflowDocument(__METHOD__)->getPublishedState();
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

        if (!$document instanceof Locali
        if ($this->inspector->getLocalizationState($document) === LocalizationState::GHOST) {
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
        return $this->inspector->getPath($this->getDocument());
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
        return $this->structure->getLocalizedProperties() ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray($complete = true)
    {
        $document = $this->getDocument();

        $result = array(
            'id' => $this->inspector->getUuid($document),
            'path' => $this->inspector->getPath($document),
            'nodeType' => $this->getNodeType(),
            'nodeState' => $this->getNodeState(),
            'internal' => false,
            'concreteLanguages' => $this->inspector->getLocales(),
            'hasSub' => $this->inspector->getChildren($document)->count() ? true : false,
            'title' => $document->getTitle(), // legacy system returns diffent fields for title depending on $complete
        );

        if ($document instanceof RedirectTypeBehavior) {
            $redirectType = $document->getRedirectType();
            if ($redirectType == RedirectType::INTERNAL) {
                $result['linked'] = 'internal';
            } elseif ($redirectType == RedirectType::EXTERNAL) {
                $result['linked'] = 'external';
            }
        }

        if ($document instanceof WorkflowStageBehavior) {
            $result['publishedState'] = $document->getWorkflowStage() === WorkflowStage::PUBLISHED;
            $result['published'] = $document->getWorkflowStage() === WorkflowStage::PUBLISHED;
        }

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

            $localizationState = $this->inspector->getLocalizationState($document);

            if (in_array(
                $localizationState,
                array(
                    LocalizationState::GHOST,
                    LocalizationState::SHADOW,
                )
            )) {
                $result['type'] = array(
                    'name' => $localizationState,
                    'value' => $this->inspector->getLocale($document),
                );
            }

            $result = array_merge($this->getDocument()->getContent()->getArrayCopy(), $result);

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
        return $this->createBridgeFromItem($this->structure->getPropertyByTagName($tagName, $highest));
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertiesByTagName($tagName)
    {
        $properties = array();
        foreach ($this->structure->getPropertiesByTagName($tagName) as $structureProperty) {
            $properties[] = $this->createBridgeFromItem($structureProperty);
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
        return $this->structure->hasTag($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeType()
    {
        $redirectType = $this->getDocument()->getRedirectType();

        if (null === $redirectType) {
            return LegacyStructure::NODE_TYPE_CONTENT;
        }

        if (RedirectType::INTERNAL == $redirectType) {
            return LegacyStructure::NODE_TYPE_INTERNAL_LINK;
        }

        if (RedirectType::EXTERNAL == $redirectType) {
            return LegacyStructure::NODE_TYPE_EXTERNAL_LINK;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown redirect type "%s"', $redirectType
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeName()
    {
        return $this->inspector->getName();
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

        $state = $this->getDocument()->getWorkflowStage();

        if ($state == WorkflowStage::PUBLISHED) {
            return StructureInterface::STATE_PUBLISHED;
        }

        return StructureInterface::STATE_TEST;
    }

    /**
     * {@inheritDoc}
     */
    public function copyFrom(StructureInterface $structure)
    {
        $this->notImplemented(__METHOD__);
    }

    private function getDocument()
    {
        if (!$this->document) {
            throw new \RuntimeException(
                'Document has not been applied to structure yet, cannot retrieve data from structure.'
            );
        }

        return $this->document;
    }

    protected function readOnlyException($method)
    {
        throw new \BadMethodCallException(sprintf(
            'Compatibility layer StructureBridge instances are readonly. Tried to call "%s"',
            $method
        ));
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

    private function documentToStructure(ContentDocumentInterface $document)
    {
        return new $this($this->inspector->getStructure($document), $this->inspector, $document);
    }
}
