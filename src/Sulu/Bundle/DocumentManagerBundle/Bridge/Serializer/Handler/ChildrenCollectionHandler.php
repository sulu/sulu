<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\DocumentManager\Collection\ChildrenCollection;

/**
 * Handle serializeation and deserialization of children collections.
 */
class ChildrenCollectionHandler implements SubscribingHandlerInterface
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
                'type' => ChildrenCollection::class,
                'method' => 'doSerialize',
            ],
        ];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param ChildrenCollection $childrenCollection
     * @param array $type
     * @param Context $context
     *
     * @return mixed
     */
    public function doSerialize(
        JsonSerializationVisitor $visitor,
        ChildrenCollection $childrenCollection,
        array $type,
        Context $context
    ) {
        $array = $childrenCollection->toArray();

        return $context->accept($array);
    }
}
