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
use Sulu\Component\Content\Document\Extension\ExtensionContainer;

/**
 * Handle serializeation and deserialization of document content.
 */
class ExtensionContainerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => ExtensionContainer::class,
                'method' => 'doSerialize',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => ExtensionContainer::class,
                'method' => 'doDeserialize',
            ],
        ];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param ExtensionContainer $container
     * @param array $type
     * @param Context $context
     *
     * @return mixed
     */
    public function doSerialize(
        JsonSerializationVisitor $visitor,
        ExtensionContainer $container,
        array $type,
        Context $context
    ) {
        return $context->accept($container->toArray());
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array $data
     * @param array $type
     * @param Context $context
     *
     * @return ExtensionContainer
     */
    public function doDeserialize(
        JsonDeserializationVisitor $visitor,
        array $data,
        array $type,
        Context $context
    ) {
        return new ExtensionContainer($data);
    }
}
