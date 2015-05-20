<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use JMS\Serializer\Annotation\HandlerCallback;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;

/**
 * Represents a parameter of a property.
 */
class PropertyParameter
{
    /**
     * @var string
     * @Type("string")
     */
    private $name;

    /**
     * @var string|bool|array
     * @Type("array<string,Sulu\Component\Content\PropertyParameter>")
     */
    private $value;

    /**
     * @var string
     * @Type("string")
     */
    private $type;

    /**
     * @var Metadata
     * @Type("Sulu\Component\Content\Metadata")
     */
    private $metadata;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string|null $type
     * @param string|bool|array $value
     * @param array $metadata
     */
    public function __construct($name, $value, $type = null, $metadata = array())
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->metadata = new Metadata($metadata);
    }

    /**
     * Returns name of property param.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns value of property param.
     *
     * @return array|bool|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns type of property param.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns title of property param.
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
     * Returns infoText of property param.
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
     * Returns placeholder of property param.
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
     * {@inheritdoc}
     */
    public function __toString()
    {
        $value = $this->getValue();

        if (is_string($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return ($value ? 'true' : 'false');
        } else {
            return '';
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

            $type = $propertyMetadata->type;
            $value = $propertyMetadata->getValue($this);
            if ($propertyName === 'value') {
                if (is_string($value)) {
                    $type = array('name' => 'string', 'params' => array());
                } elseif (is_bool($value)) {
                    $type = array('name' => 'boolean', 'params' => array());
                }

                $data['valueType'] = $type;
            }

            $data[$propertyName] = $graphNavigator->accept(
                $value,
                $type,
                $context
            );
            $context->popPropertyMetadata();
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

                $type = $propertyMetadata->type;
                if ($propertyName === 'value') {
                    $type = $data['valueType'];
                }

                $value = $graphNavigator->accept(
                    $data[$propertyName],
                    $type,
                    $context
                );
                $context->popPropertyMetadata();

                $propertyMetadata->setValue($this, $value);
            }
        }

        return $data;
    }
}
