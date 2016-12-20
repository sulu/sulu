<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use JMS\Serializer\Annotation\Type;
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
    private $col;

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
        $col = null
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
        $this->col = $col;
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
     * @param PropertyTag $tag
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
    public function getColspan()
    {
        return $this->col;
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
        return $this->metadata->get('title', $languageCode, ucfirst($this->name));
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
     *
     * @param mixed $value
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
     *
     * @return mixed
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

        if (is_null($minOccurs) && is_null($maxOccurs)) {
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
     * @param $property
     */
    public function __get($property)
    {
        if (method_exists($this, 'get' . ucfirst($property))) {
            return $this->{'get' . ucfirst($property)}();
        } else {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
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
        $value = $this->getValue();
        if (is_object($value)) {
            $value = clone $value;
        }
        $this->setValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        if ($this->getValue() instanceof ArrayableInterface) {
            return $this->getValue()->toArray($depth);
        } else {
            return $this->getValue();
        }
    }
}
