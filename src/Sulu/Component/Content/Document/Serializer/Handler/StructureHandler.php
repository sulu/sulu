<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Content\Document\Structure\Structure;

/**
 * Handle serializeation and deserialization of document content.
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
     * @param NodeInterface            $nodeInterface
     * @param array                    $type
     * @param Context                  $context
     */
    public function doSerialize(
        JsonSerializationVisitor $visitor,
        Structure $container,
        array $type,
        Context $context
    ) {
        $array = $container->toArray();

        return $context->accept($array);
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param NodeInterface            $nodeInterface
     * @param array                    $type
     * @param Context                  $context
     */
    public function doDeserialize(
        JsonDeserializationVisitor $visitor,
        array $data,
        array $type,
        Context $context
    ) {
        $container = new Structure();

        foreach ($data as $key => $value) {
            $container->getProperty($key)->setValue($value);
        }

        return $container;
    }
}
