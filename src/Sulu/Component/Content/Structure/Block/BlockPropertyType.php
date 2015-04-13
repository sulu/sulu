<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure\Block;

use Sulu\Component\Content\Structure\Metadata;
use Sulu\Component\Content\Structure\PropertyInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use JMS\Serializer\Annotation\Type;

/**
 * representation of a block type node in template xml
 */
class BlockPropertyType
{
    /**
     * @var string
     * @Type("string")
     */
    private $name;

    /**
     * @var Metadata
     * @Type("Sulu\Component\Content\Metadata")
     */
    private $metadata;

    /**
     * properties managed by this block
     * @var PropertyInterface[]
     * @Type("array<Sulu\Component\Content\Property>")
     */
    private $childProperties = array();

    public function __construct($name, $metadata)
    {
        $this->name = $name;
        $this->metadata = new Metadata($metadata);
    }

    /**
     * returns a list of properties managed by this block
     * @return PropertyInterface[]
     */
    public function getChildProperties()
    {
        return $this->childProperties;
    }

    /**
     * returns child property with given name
     * @param string $name
     * @return null|PropertyInterface
     */
    public function getProperty($name)
    {
        foreach ($this->getChildProperties() as $property) {
            if ($property->getName() === $name) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @param PropertyInterface $property
     */
    public function addChild(PropertyInterface $property)
    {
        $this->childProperties[] = $property;
    }

    /**
     * returns property with given name
     * @param string $name of property
     * @throws NoSuchPropertyException
     * @return PropertyInterface
     */
    public function getChild($name)
    {
        foreach ($this->childProperties as $child) {
            if ($child->getName() === $name) {
                return $child;
            }
        }
        throw new NoSuchPropertyException($name, 'Property does not exist');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $languageCode
     * @return string
     */
    public function getTitle($languageCode)
    {
        return $this->metadata->get('title', $languageCode, ucfirst($this->getName()));
    }

    /**
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    public function __clone()
    {
        $properties = $this->childProperties;
        $this->childProperties = array();
        foreach ($properties as $childProperty) {
            $this->addChild(clone $childProperty);
        }
    }
}
