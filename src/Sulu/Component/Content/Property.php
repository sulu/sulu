<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use JMS\Serializer\Annotation\Discriminator;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\HandlerCallback;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Component\Content\Block\BlockPropertyInterface;
use Sulu\Component\Content\Section\SectionPropertyInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Property of Structure generated from Structure Manager to map a template.
 *
 * @Discriminator(
 *     field = "propertyType",
 *     map = {
 *         "property": "Sulu\Component\Content\Property",
 *         "block": "Sulu\Component\Content\Block\BlockProperty",
 *         "section": "Sulu\Component\Content\Section\SectionProperty"
 *     }
 * )
 */
class Property implements PropertyInterface, \JsonSerializable
{
    /**
     * name of property.
     *
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
     * is property mandatory.
     *
     * @var bool
     * @Type("boolean")
     */
    private $mandatory;

    /**
     * is property multilingual.
     *
     * @var bool
     * @Type("boolean")
     */
    private $multilingual;

    /**
     * min occurs of property value.
     *
     * @var int
     * @Type("integer")
     */
    private $minOccurs;

    /**
     * max occurs of property value.
     *
     * @var int
     * @Type("integer")
     */
    private $maxOccurs;

    /**
     * name of content type.
     *
     * @var string
     * @Type("string")
     */
    private $contentTypeName;

    /**
     * parameter of property to merge with parameter of content type.
     *
     * @var array
     * @Type("array<string,Sulu\Component\Content\PropertyParameter>")
     */
    private $params;

    /**
     * tags defined in xml.
     *
     * @var PropertyTag[]
     * @Type("array<Sulu\Component\Content\PropertyTag>")
     */
    private $tags;

    /**
     * column span.
     *
     * @var string
     * @Type("string")
     */
    private $col;

    /**
     * value of property.
     *
     * @var mixed
     * @Exclude
     */
    private $value;

    /**
     * @var StructureInterface
     * @Exclude
     */
    private $structure;

    /**
     * Constructor.
     */
    public function __construct(
        $name,
        $metaData,
        $contentTypeName,
        $mandatory = false,
        $multilingual = true,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = array(),
        $tags = array(),
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
     * @return \Sulu\Component\Content\PropertyTag[]
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
        $this->value = $value;
    }

    /**
     * gets the value from property.
     *
     * @return mixed
     */
    public function getValue()
    {
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
        return $this->minOccurs > 1 || $this->maxOccurs > 1;
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
        $result = array(
            'name' => $this->getName(),
            'metadata' => $this->getMetadata()->getData(),
            'mandatory' => $this->getMandatory(),
            'multilingual' => $this->getMultilingual(),
            'minOccurs' => $this->getMinOccurs(),
            'maxOccurs' => $this->getMaxOccurs(),
            'contentTypeName' => $this->getContentTypeName(),
            'params' => $this->getParams(),
            'tags' => array(),
        );
        foreach ($this->getTags() as $tag) {
            $result['tags'][] = array(
                'name' => $tag->getName(),
                'priority' => $tag->getPriority(),
            );
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

    /**
     * @HandlerCallback("json", direction = "serialization")
     */
    public function serializeToJson(JsonSerializationVisitor $visitor, $data, Context $context)
    {
        $classMetadata = $context->getMetadataFactory()->getMetadataForClass(get_class($this));
        $graphNavigator = $context->getNavigator();

        $data = array();

        /**
         * @var string
         * @var PropertyMetadata
         */
        foreach ($classMetadata->propertyMetadata as $propertyName => $propertyMetadata) {
            $context->pushPropertyMetadata($propertyMetadata);
            $data[$propertyName] = $graphNavigator->accept(
                $propertyMetadata->getValue($this),
                $propertyMetadata->type,
                $context
            );
            $context->popPropertyMetadata();
        }

        $data['value'] = json_encode($this->getValue());

        // set discriminator value
        if ($this instanceof BlockPropertyInterface) {
            $data['propertyType'] = 'block';
        } elseif ($this instanceof SectionPropertyInterface) {
            $data['propertyType'] = 'section';
        } else {
            $data['propertyType'] = 'property';
        }

        return $data;
    }

    /**
     * @HandlerCallback("json", direction = "deserialization")
     */
    public function deserializeToJson(JsonDeserializationVisitor $visitor, $data, Context $context)
    {
        $classMetadata = $context->getMetadataFactory()->getMetadataForClass(get_class($this));
        $graphNavigator = $context->getNavigator();

        /**
         * @var string
         * @var PropertyMetadata
         */
        foreach ($classMetadata->propertyMetadata as $propertyName => $propertyMetadata) {
            if (!($propertyMetadata instanceof StaticPropertyMetadata)) {
                $context->pushPropertyMetadata($propertyMetadata);
                $value = $graphNavigator->accept(
                    $data[$propertyName],
                    $propertyMetadata->type,
                    $context
                );
                $context->popPropertyMetadata();

                $propertyMetadata->setValue($this, $value);
            }
        }

        $this->setValue(json_decode($data['value'], true));
        $this->structure = $visitor->getResult();

        return $data;
    }
}
