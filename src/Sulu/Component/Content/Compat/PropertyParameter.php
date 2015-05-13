<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\HandlerCallback;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;

/**
 * Represents a parameter of a property
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
     * @Type("array<string,Sulu\Component\Content\Compat\PropertyParameter>")
     */
    private $value;

    /**
     * @var string
     * @Type("string")
     */
    private $type;

    /**
     * @var Metadata
     * @Type("Sulu\Component\Content\Compat\Metadata")
     */
    private $metadata;

    /**
     * Constructor
     * @param string $name
     * @param string|null $type
     * @param string|bool|array $value
     * @param array $metadata
     */
    function __construct($name, $value, $type = null, $metadata = array())
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
        $this->metadata = new Metadata($metadata);
    }

    /**
     * Returns name of property param
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns value of property param
     * @return array|bool|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns type of property param
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns title of property param
     * @param string $languageCode
     * @return string
     */
    public function getTitle($languageCode)
    {
        return $this->metadata->get('title', $languageCode, ucfirst($this->name));
    }

    /**
     * Returns infoText of property param
     * @param string $languageCode
     * @return string
     */
    public function getInfoText($languageCode)
    {
        return $this->metadata->get('info_text', $languageCode, '');
    }

    /**
     * Returns placeholder of property param
     * @param string $languageCode
     * @return string
     */
    public function getPlaceholder($languageCode)
    {
        return $this->metadata->get('placeholder', $languageCode, '');
    }

    /**
     * {@inheritdoc}
     */
    function __toString()
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
}
