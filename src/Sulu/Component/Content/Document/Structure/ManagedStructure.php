<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Structure;

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Lazy loading container for content properties.
 */
class ManagedStructure extends Structure
{
    /**
     * @var StructureMetadata
     */
    private $structureMetadata;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var array<string, PropertyInterface>
     */
    private $legacyProperties = [];

    /**
     * @var array<string, PropertyValue>
     */
    private $propertyValues = [];

    /**
     * @param object $document
     */
    public function __construct(
        private ContentTypeManagerInterface $contentTypeManager,
        private LegacyPropertyFactory $legacyPropertyFactory,
        private DocumentInspector $inspector,
        private $document,
    ) {
    }

    public function getProperty($name)
    {
        $this->init();

        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        if (!$this->node) {
            $this->node = $this->inspector->getNode($this->document);
        }

        $structureProperty = $this->structureMetadata->getProperty($name);

        $contentTypeName = $structureProperty->getType();

        $bridge = new StructureBridge(
            $this->structureMetadata,
            $this->inspector,
            $this->legacyPropertyFactory,
            $this->document
        );

        if ($structureProperty->isLocalized()) {
            $locale = $this->inspector->getLocale($this->document);
            $property = $this->legacyPropertyFactory->createTranslatedProperty($structureProperty, $locale, $bridge);
        } else {
            $property = $this->legacyPropertyFactory->createProperty($structureProperty);
        }

        $this->legacyProperties[$name] = $property;

        $property->setStructure($bridge);

        $contentType = $this->contentTypeManager->get($contentTypeName);
        $contentType->read(
            $this->node,
            $property,
            $bridge->getWebspaceKey(),
            $bridge->getLanguageCode(),
            null
        );

        $valueProperty = new PropertyValue($name, $property->getValue());
        $this->properties[$name] = $valueProperty;

        return $valueProperty;
    }

    public function getContentViewProperty($name)
    {
        if (isset($this->propertyValues[$name])) {
            return $this->propertyValues[$name];
        }

        // initialize the legacy property
        $this->getProperty($name);
        $legacyProperty = $this->legacyProperties[$name];

        $structureProperty = $this->structureMetadata->getProperty($name);
        $contentTypeName = $structureProperty->getType();
        $contentType = $this->contentTypeManager->get($contentTypeName);
        $propertyValue = new PropertyValue(
            $name,
            $contentType->getContentData($legacyProperty)
        );
        $this->propertyValues[$name] = $propertyValue;

        return $propertyValue;
    }

    /**
     * Update the structure.
     */
    public function setStructureMetadata(StructureMetadata $structure)
    {
        $this->structureMetadata = $structure;
    }

    /**
     * Return an array copy of the property data.
     *
     * @return array
     */
    public function toArray()
    {
        $this->init();
        $values = [];
        foreach (\array_keys($this->structureMetadata->getProperties()) as $childName) {
            $values[$childName] = $this->normalize($this->getProperty($childName)->getValue());
        }

        return $values;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        $this->init();

        return $this->structureMetadata->hasProperty($offset);
    }

    public function bind($data, $clearMissing = true)
    {
        $this->init();

        foreach ($this->structureMetadata->getProperties() as $childName => $child) {
            if (false === $clearMissing && !\array_key_exists($childName, $data)) {
                continue;
            }

            $value = isset($data[$childName]) ? $data[$childName] : null;

            $property = $this->getProperty($childName);
            $property->setValue($value);
        }
    }

    private function init()
    {
        if (!$this->structureMetadata) {
            $this->structureMetadata = $this->inspector->getStructureMetadata($this->document);
        }
    }
}
