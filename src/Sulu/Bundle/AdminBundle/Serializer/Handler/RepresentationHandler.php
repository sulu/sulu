<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Serializer\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\ListBuilder\RepresentationInterface;
use Sulu\Component\SmartContent\Rest\ItemCollectionRepresentation;

/**
 * @internal
 *
 * This handler workaround some problems with serialize Representation in specific groups
 */
class RepresentationHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => CollectionRepresentation::class,
                'method' => 'serializeForJson',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => PaginatedRepresentation::class,
                'method' => 'serializeForJson',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => ItemCollectionRepresentation::class,
                'method' => 'serializeForJson',
            ],
        ];
    }

    public function serializeForJson(
        JsonSerializationVisitor $visitor,
        RepresentationInterface $representation,
        array $type,
        Context $context
    ) {
        $context->getNavigator()->accept($representation->toArray(), null, $context);

        return $context;
    }
}
