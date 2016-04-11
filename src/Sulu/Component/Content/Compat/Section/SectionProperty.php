<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Section;

use JMS\Serializer\Annotation\HandlerCallback;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;

/**
 * Defines a section for properties.
 */
class SectionProperty extends Property implements SectionPropertyInterface
{
    /**
     * properties managed by this block.
     *
     * @var PropertyInterface[]
     * @Type("array<Sulu\Component\Content\Compat\Property>")
     */
    private $childProperties = [];

    /**
     * @param string $name
     * @param array  $metadata
     * @param string $col
     */
    public function __construct($name, $metadata, $col)
    {
        parent::__construct($name, $metadata, 'section', false, false, 1, 1, [], [], $col);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildProperties()
    {
        return $this->childProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function addChild(PropertyInterface $property)
    {
        $this->childProperties[] = $property;
    }

    /**
     * @HandlerCallback("json", direction = "serialization")
     */
    public function serializeToJson(JsonSerializationVisitor $visitor, $data, Context $context)
    {
        return parent::serializeToJson($visitor, $data, $context);
    }

    /**
     * @HandlerCallback("json", direction = "deserialization")
     */
    public function deserializeToJson(JsonDeserializationVisitor $visitor, $data, Context $context)
    {
        return parent::deserializeToJson($visitor, $data, $context);
    }
}
