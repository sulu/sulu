<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Property of Structure generated from Structure Manager to map a template.
 */
class Property implements PropertyInterface, \JsonSerializable
{
    /**
     * name of property.
     *
     * @var string
     */
    private $name;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * is property mandatory.
     *
     * @var bool
     */
    private $mandatory;

    /**
     * is property multilingual.
     *
     * @var bool
     */
    private $multilingual;

    /**
     * min occurs of property value.
     *
     * @var int
     */
    private $minOccurs;

    /**
     * max occurs of property value.
     *
     * @var int
     */
    private $maxOccurs;

    /**
     * name of content type.
     *
     * @var string
     */
    private $contentTypeName;

    /**
     * parameter of property to merge with parameter of content type.
     *
     * @var array
     */
    private $params;

    /**
     * tags defined in xml.
     *
     * @var PropertyTag[]
     */
    private $tags;

    /**
     * column span.
     *
     * @var string
     */
    private $colSpan;

    /**
     * value of property.
     *
     * @var mixed
     */
    private $value;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * Constructor.
     *
     * @var PropertyValue
     */
    protected $propertyValue;

    /**
     * properties managed by this block.
     *
     * @var PropertyType[]
     */
    protected $types = [];

    /**
     * @var PropertyType[]
     */
    protected $properties = [];

    /**
     * @var string|null
     */
    protected $defaultTypeName;

    public function __construct(
        $name,
        $metaData,
        $contentTypeName,
        $mandatory = false,
        $multilingual = true,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = [],
        $tags = [],
        $colSpan = null,
        $defaultTypeName = null
    ) {
        $this->contentTypeName = $contentTypeName;
        $this->mandatory = $mandatory;
        $this->maxOccurs = $maxOccurs;
        $this->minOccurs = $minOccurs;
        $this->multilingual = $multilingual;
        $this->name = $name;
        $this->metadata = new Metadata($metaData);
        $this->params = $params;
        $this->tags = $tags;
        $this->colSpan = $colSpan;
        $this->defaultTypeName = $defaultTypeName;
    }

    public function setPropertyValue(PropertyValue $propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }

    /**
     * returns name of template.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns mandatory.
     *
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * returns multilingual.
     *
     * @return bool
     */
    public function isMultilingual()
    {
        return $this->multilingual;
    }

    /**
     * return min occurs.
     *
     * @return int
     */
    public function getMinOccurs()
    {
        return $this->minOccurs;
    }

    /**
     * return max occurs.
     *
     * @return int
     */
    public function getMaxOccurs()
    {
        return $this->maxOccurs;
    }

    /**
     * returns field is mandatory.
     *
     * @return bool
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * returns field is multilingual.
     *
     * @return bool
     */
    public function getMultilingual()
    {
        return $this->multilingual;
    }

    /**
     * returns tags defined in xml.
     *
     * @return \Sulu\Component\Content\Compat\PropertyTag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * returns tag with given name.
     *
     * @param string $tagName
     *
     * @return PropertyTag
     */
    public function getTag($tagName)
    {
        return $this->tags[$tagName];
    }

    /**
     * add a property tag.
     *
     * @return PropertyTag
     */
    public function addTag(PropertyTag $tag)
    {
        return $this->tags[$tag->getName()] = $tag;
    }

    /**
     * return true if a tag with the given name exists.
     *
     * @return bool
     */
    public function hasTag($tagName)
    {
        return isset($this->tags[$tagName]);
    }

    /**
     * returns column span.
     *
     * @return string
     */
    public function getColSpan()
    {
        return $this->colSpan;
    }

    /**
     * returns title of property.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getTitle($languageCode)
    {
        return $this->metadata->get('title', $languageCode, \ucfirst($this->name));
    }

    /**
     * returns infoText of property.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getInfoText($languageCode)
    {
        return $this->metadata->get('info_text', $languageCode, '');
    }

    /**
     * returns placeholder of property.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getPlaceholder($languageCode)
    {
        return $this->metadata->get('placeholder', $languageCode, '');
    }

    /**
     * sets the value from property.
     */
    public function setValue($value)
    {
        if ($this->propertyValue) {
            $this->propertyValue->setValue($value);
        }

        $this->value = $value;
    }

    public function setValueByReference(&$value)
    {
        $this->value = $value;
    }

    /**
     * gets the value from property.
     */
    public function getValue()
    {
        if ($this->propertyValue) {
            return $this->propertyValue->getValue();
        }

        return $this->value;
    }

    /**
     * returns name of content type.
     *
     * @return string
     */
    public function getContentTypeName()
    {
        return $this->contentTypeName;
    }

    /**
     * parameter of property.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * returns TRUE if property is a block.
     *
     * @return bool
     */
    public function getIsBlock()
    {
        return false;
    }

    /**
     * returns TRUE if property is multiple.
     *
     * @return bool
     */
    public function getIsMultiple()
    {
        $minOccurs = $this->getMinOccurs();
        $maxOccurs = $this->getMaxOccurs();

        if (\is_null($minOccurs) && \is_null($maxOccurs)) {
            // if no occurs attributes are set it defaults to false
            return false;
        }

        if (0 === $minOccurs && 1 === $maxOccurs) {
            // this one allows to have an optional field
            return true;
        }

        if (1 === $minOccurs && 1 === $maxOccurs) {
            // if the occurences have a high and low limit of 1 it should be displayed as single
            return false;
        }

        // if minOccurs is set a value of 1 is enough, because maxOccurs would be considered "unbound" when null
        return $minOccurs >= 1 || $maxOccurs > 1;
    }

    /**
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * returns structure.
     *
     * @return StructureInterface
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * @param StructureInterface $structure
     */
    public function setStructure($structure)
    {
        $this->structure = $structure;
    }

    /**
     * magic getter for twig templates.
     *
     * @param string $property
     */
    public function __get($property)
    {
        if (\method_exists($this, 'get' . \ucfirst($property))) {
            return $this->{'get' . \ucfirst($property)}();
        } else {
            return;
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $result = [
            'name' => $this->getName(),
            'metadata' => $this->getMetadata()->getData(),
            'mandatory' => $this->getMandatory(),
            'multilingual' => $this->getMultilingual(),
            'minOccurs' => $this->getMinOccurs(),
            'maxOccurs' => $this->getMaxOccurs(),
            'contentTypeName' => $this->getContentTypeName(),
            'params' => $this->getParams(),
            'tags' => [],
        ];
        foreach ($this->getTags() as $tag) {
            $result['tags'][] = [
                'name' => $tag->getName(),
                'priority' => $tag->getPriority(),
            ];
        }

        return $result;
    }

    public function __clone()
    {
        $clone = new self(
            $this->getName(),
            $this->getMetadata(),
            $this->getContentTypeName(),
            $this->getMandatory(),
            $this->getMultilingual(),
            $this->getMaxOccurs(),
            $this->getMinOccurs(),
            $this->getParams(),
            $this->getTags(),
            $this->getColSpan(),
            $this->getDefaultTypeName()
        );

        $clone->types = [];
        foreach ($this->types as $type) {
            $clone->addType(clone $type);
        }

        $clone->setValue($this->getValue());

        return $clone;
    }

    public function toArray($depth = null)
    {
        if ($this->getValue() instanceof ArrayableInterface) {
            return $this->getValue()->toArray($depth);
        } else {
            return $this->getValue();
        }
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function addType($type)
    {
        $this->types[$type->getName()] = $type;
    }

    public function getType($name)
    {
        if (!$this->hasType($name)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'The block type "%s" has not been registered. Known block types are: [%s]',
                    $name,
                    \implode(', ', \array_keys($this->types))
                )
            );
        }

        return $this->types[$name];
    }

    public function hasType($name)
    {
        return isset($this->types[$name]);
    }

    public function getDefaultTypeName()
    {
        return $this->defaultTypeName;
    }

    /**
     * returns child properties of given Type.
     *
     * @param string $typeName
     *
     * @return PropertyInterface[]
     */
    public function getTypeChildProperties($typeName)
    {
        return $this->getType($typeName)->getChildProperties();
    }

    public function initProperties($index, $typeName)
    {
        $type = $this->getType($typeName);
        $this->properties[$index] = clone $type;

        return $this->properties[$index];
    }

    public function clearProperties()
    {
        $this->properties = [];
    }

    public function getProperties($index)
    {
        if (!isset($this->properties[$index])) {
            throw new \OutOfRangeException(\sprintf(
                'No properties at index "%s" in block "%s". Valid indexes: [%s]',
                $index, $this->getName(), \implode(', ', \array_keys($this->properties))
            ));
        }

        return $this->properties[$index];
    }

    public function getLength()
    {
        return \count($this->properties);
    }
}
