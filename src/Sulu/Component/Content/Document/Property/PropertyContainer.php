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
class PropertyContainer implements \ArrayAccess
{
    private $contentTypeManager;
    private $structure;
    private $node;
    private $document;
    private $propertyEncoder;

    private $properties = array();

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

        if (true === $structureProperty->isLocalized()) {
            $locale = $this->document->getLocale();
            $name = $this->propertyEncoder->localizedContentName($name, $locale);
        } else {
            $name = $this->propertyEncoder->contentname($name);
        }

        $property = new Property($name, $this->document);
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

    public function offsetExists($offset)
    {
        return $this->structure->hasProperty($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getProperty($offset)->getValue();
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(
            'Cannot set content properties objects'
        );
    }

    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }
}
