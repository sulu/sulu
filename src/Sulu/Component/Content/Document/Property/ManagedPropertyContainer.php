<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document\Property;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Property\PropertyValue;

/**
 * Lazy loading container for content properties.
 */
class ManagedPropertyContainer extends PropertyContainer
{
    private $contentTypeManager;
    private $document;
    private $legacyPropertyFactory;
    private $inspector;
    private $structure;
    private $node;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param NodeInterface $node
     * @param Structure $structure
     * @param object $document
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        LegacyPropertyFactory $legacyPropertyFactory,
        DocumentInspector $inspector,
        $document
    )
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->document = $document;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
        $this->inspector = $inspector;
    }

    /**
     * Return the named property and evaluate its content
     *
     * @param string $name
     */
    public function getProperty($name)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        if (!$this->node) {
            $this->node = $this->inspector->getNode($this->document);
        }

        if (!$this->structure) {
            $this->structure = $this->inspector->getStructure($this->document);
        }

        $structureProperty = $this->structure->getProperty($name);

        $contentTypeName = $structureProperty->getType();

        if ($structureProperty->isLocalized()) {
            $locale = $this->inspector->getLocale($this->document);
            $property = $this->legacyPropertyFactory->createTranslatedProperty($structureProperty, $locale);
        } else {
            $property = $this->legacyPropertyFactory->createProperty($structureProperty);
        }

        $contentType = $this->contentTypeManager->get($contentTypeName);
        $contentType->read(
            $this->node,
            $property,
            null,
            null,
            null
        );

        $valueProperty = new PropertyValue($name, $property->getValue());
        $this->properties[$name] = $valueProperty;

        return $valueProperty;
    }

    /**
     * Update the structure
     *
     * @param Structure $structure
     */
    public function setStructure(Structure $structure) 
    {
        $this->structure = $structure;
    }

    /**
     * Return an array copy of the property data
     *
     * @return array
     */
    public function getArrayCopy()
    {
        $values = array();
        foreach ($this->structure->getChildren() as $childName => $structureChild) {
            $values[$childName] = $this->getProperty($childName)->getValue();
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return $this->structure->hasProperty($offset);
    }
}
