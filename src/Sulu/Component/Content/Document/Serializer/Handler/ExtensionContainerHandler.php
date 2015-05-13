<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Serializer\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;

/**
 * Handle serializeation and deserialization of document content
 */
class ExtensionContainerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => ExtensionContainer::class,
                'method' => 'doSerialize',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => ExtensionContainer::class,
                'method' => 'doDeserialize',
            ),
        );
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param NodeInterface $nodeInterface
     * @param array $type
     * @param Context $context
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
     * @param JsonSerializationVisitor $visitor
     * @param NodeInterface $nodeInterface
     * @param array $type
     * @param Context $context
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
