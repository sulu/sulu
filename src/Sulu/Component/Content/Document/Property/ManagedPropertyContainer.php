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

use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Structure\Structure;

/**
 * Lazy loading container for content properties.
 */
class ManagedPropertyContainer extends PropertyContainer
{
    private $contentTypeManager;
    private $structure;
    private $node;
    private $document;
    private $propertyEncoder;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param NodeInterface $node
     * @param Structure $structure
     * @param object $document
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        NodeInterface $node,
        PropertyEncoder $encoder,
        Structure $structure,
        $document
    )
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->structure = $structure;
        $this->node = $node;
        $this->document = $document;
        $this->propertyEncoder = $encoder;
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

        $contentTypeName = $structureProperty->getType();

        // TODO: Use inspector to get locale
        $locale = $this->document->getLocale();
        $phpcrName = $this->propertyEncoder->fromProperty($structureProperty, $locale);

        $property = new Property($phpcrName, $this->document);
        $property->setStructureProperty($structureProperty);
        $this->properties[$name] = $property;

        $contentType = $this->contentTypeManager->get($contentTypeName);
        $contentType->read(
            $this->node,
            $property,
            null,
            null,
            null
        );

        return $property;
    }

    public function setStructure(Structure $structure) 
    {
        $this->structure = $structure;
    }

    public function getArrayCopy()
    {
        $values = array();
        foreach ($this->structure->getChildren() as $childName => $structureChild) {
            $values[$childName] = $this->getProperty($childName)->getValue();
        }

        return $values;
    }

    public function offsetExists($offset)
    {
        return $this->structure->hasProperty($offset);
    }
}
