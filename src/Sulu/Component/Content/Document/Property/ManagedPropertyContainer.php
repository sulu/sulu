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

/**
 * Lazy loading container for content properties.
 */
class ManagedPropertyContainer extends PropertyContainer
{
    private $contentTypeManager;
    private $structure;
    private $node;
    private $document;
    private $legacyPropertyFactory;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param NodeInterface $node
     * @param Structure $structure
     * @param object $document
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        LegacyPropertyFactory $legacyPropertyFactory,
        NodeInterface $node,
        Structure $structure,
        $document
    )
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->structure = $structure;
        $this->node = $node;
        $this->document = $document;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
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

        $structureProperty = $this->structure->getProperty($name);

        $contentTypeName = $structureProperty->getContentTypeName();

        // TODO: Use inspector to get locale
        $property = $this->legacyPropertyFactory->createTranslatedPropertyFrom($structureProperty, $locale);

        $contentType = $this->contentTypeManager->get($contentTypeName);
        $contentType->read(
            $this->node,
            $structureProperty,
            null,
            null,
            null
        );

        $valueProperty = new ValueProperty($name);
        $valueProperty->setValue($structureProperty->getValue());
        $this->properties[$name] = $valueProperty;

        return $property;
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
