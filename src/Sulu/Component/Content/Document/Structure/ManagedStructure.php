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

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Property\PropertyValue;
use Sulu\Component\Content\Compat\Structure\StructureBridge;

/**
 * Lazy loading container for content properties.
 */
class ManagedStructure extends Structure
{
    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var object
     */
    private $document;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var StructureMetadata
     */
    private $structureMetadata;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param LegacyPropertyFactory $propertyFactory
     * @param DocumentInspector $inspector
     * @param object $document
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        LegacyPropertyFactory $propertyFactory,
        DocumentInspector $inspector,
        $document
    )
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->document = $document;
        $this->propertyFactory = $propertyFactory;
        $this->inspector = $inspector;
    }

    /**
     * Return the named property and evaluate its content
     *
     * Lazy loads from the Compat system.
     *
     * @param string $name
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

        $structureProperty = $this->structureMetadata->getProperty($name);
        $contentTypeName = $structureProperty->getType();

        if ($structureProperty->isLocalized()) {
            $locale = $this->inspector->getLocale($this->document);
            $property = $this->propertyFactory->createTranslatedProperty($structureProperty, $locale);
        } else {
            $property = $this->propertyFactory->createProperty($structureProperty);
        }

        $bridge = new StructureBridge($this->structureMetadata, $this->inspector, $this->propertyFactory, $this->document);
        $property->setStructure($bridge);

        $contentType = $this->contentTypeManager->get($contentTypeName);
        $contentType->read(
            $this->node,
            $property,
            null,
            null,
            null
        );

        $valueProperty = new Property($name, $property->getValue());
        $this->properties[$name] = $valueProperty;

        return $valueProperty;
    }

    /**
     * Update the structure
     *
     * @param StructureMetadata $structure
     */
    public function setStructure(StructureMetadata $structureMetadata)
    {
        $this->structureMetadata = $structureMetadata;
    }

    /**
     * Return an array copy of the property data
     *
     * @return array
     */
    public function toArray()
    {
        $this->init();
        $values = array();
        foreach (array_keys($this->structureMetadata->getProperties()) as $childName) {
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
        return $this->structureMetadata->hasProperty($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function bind($data, $clearMissing = true)
    {
        foreach (array_keys($this->structureMetadata->getProperties()) as $childName) {
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
        if ($this->structureMetadata) {
            return;
        }

        $this->structureMetadata = $this->inspector->getStructure($this->document);
    }
}
