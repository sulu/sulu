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
use Sulu\Component\Content\Document\Property\PropertyContainer;

/**
 * Handle serializeation and deserialization of document content
 */
class PropertyContainerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => PropertyContainer::class,
                'method' => 'doSerialize',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => PropertyContainer::class,
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
        PropertyContainer $container,
        array $type,
        Context $context
    ) {
        $array = $container->toArray();

        return $context->accept(array(
            'content' => $array,
        ));
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
        $container = new PropertyContainer();

        $content = $data['content'];

        foreach ($content as $key => $value) {
            $container->getProperty($key)->setValue($value);
        }

        return $container;
    }

}
