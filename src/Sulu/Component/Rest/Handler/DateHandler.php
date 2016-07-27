<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Handler;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;

/**
 * Serializes Date for array serializer.
 */
class DateHandler implements SubscribingHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods()
    {
        return [
            [
                'type' => 'DateTime',
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'array',
                'method' => 'deserialize',
            ],
            [
                'type' => 'DateTime',
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'array',
                'method' => 'serialize',
            ],
        ];
    }

    public function serialize(VisitorInterface $visitor, $date, array $type, Context $context)
    {
        return $date;
    }

    public function deserialize(VisitorInterface $visitor, $date, array $type, Context $context)
    {
        return $date;
    }
}
