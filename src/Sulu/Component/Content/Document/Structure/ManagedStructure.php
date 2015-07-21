<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Structure;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Lazy loading container for content properties.
 */
class ManagedStructure extends Structure
{
    private $contentTypeManager;
    private $document;
    private $legacyPropertyFactory;
    private $inspector;
    private $structure;
    private $node;
    private $legacyProperties = array();
    private $contentViewProperties = array();

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param LegacyPropertyFactory $legacyPropertyFactory
     * @param DocumentInspector $inspector
     * @param object $document
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        LegacyPropertyFactory $legacyPropertyFactory,
        DocumentInspector $inspector,
        $document
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->document = $document;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
        $this->inspector = $inspector;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name)
    {
        $this->init();

        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        if (!$this->node) {
            $this->node = $this->inspector->getNode($this->document);
        }

        $structureProperty = $this->structure->getProperty($name);

        $contentTypeName = $structureProperty->getType();

        if ($structureProperty->isLocalized()) {
            $locale = $this->inspector->getLocale($this->document);
            $property = $this->legacyPropertyFactory->createTranslatedProperty($structureProperty, $locale);
        } else {
            $property = $this->legacyPropertyFactory->createProperty($structureProperty);
        }

        $this->legacyProperties[$name] = $property;

        $bridge = new StructureBridge($this->structure, $this->inspector, $this->legacyPropertyFactory, $this->document);
        $property->setStructure($bridge);

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
     * {@inheritDoc}
     */
    public function getContentViewProperty($name)
    {
        if (isset($this->contentViewProperties[$name])) {
            return $this->contentViewProperties[$name];
        }

        // initialize the legacy property
        $this->getProperty($name);
        $legacyProperty = $this->legacyProperties[$name];

        $structureProperty = $this->structure->getProperty($name);
        $contentTypeName = $structureProperty->getType();
        $contentType = $this->contentTypeManager->get($contentTypeName);
        $propertyValue = new PropertyValue(
            $name,
            $contentType->getContentData($legacyProperty)
        );
        $this->contentViewProperties[$name] = $propertyValue;

        return $propertyValue;
    }

    /**
     * Update the structure.
     *
     * @param StructureMetadata $structure
     */
    public function setStructureMetadata(StructureMetadata $structure)
    {
        $this->structure = $structure;
    }

    /**
     * Return an array copy of the property data.
     *
     * @return array
     */
    public function toArray()
    {
        $this->init();
        $values = array();
        foreach (array_keys($this->structure->getProperties()) as $childName) {
            $values[$childName] = $this->normalize($this->getProperty($childName)->getValue());
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        $this->init();

        return $this->structure->hasProperty($offset);
    }

    public function bind($data, $clearMissing = true)
    {
        foreach ($this->structure->getProperties() as $childName => $child) {
            if (false === $clearMissing && !isset($data[$childName])) {
                continue;
            }

            $value = isset($data[$childName]) ? $data[$childName] : null;

            $property = $this->getProperty($childName);
            $property->setValue($value);
        }
    }

    private function init()
    {
        if (!$this->structure) {
            $this->structure = $this->inspector->getStructureMetadata($this->document);
        }
    }
}
