<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Content\Document\Structure\Structure;

/**
 * Handle serialization and deserialization of document content.
 */
class StructureHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Structure::class,
                'method' => 'doSerialize',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => Structure::class,
                'method' => 'doDeserialize',
            ],
        ];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Structure $structure
     * @param array $type
     * @param Context $context
     *
     * @return mixed
     */
    public function doSerialize(
        JsonSerializationVisitor $visitor,
        Structure $structure,
        array $type,
        Context $context
    ) {
        $array = $structure->toArray();

        return $context->accept($array);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array $data
     * @param array $type
     * @param Context $context
     *
     * @return Structure
     */
    public function doDeserialize(
        JsonDeserializationVisitor $visitor,
        array $data,
        array $type,
        Context $context
    ) {
        $structure = new Structure();

        foreach ($data as $key => $value) {
            $structure->getProperty($key)->setValue($value);
        }

        return $structure;
    }
}
