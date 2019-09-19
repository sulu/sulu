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

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\ListBuilder\RepresentationInterface;
use Sulu\Component\SmartContent\Rest\ItemCollectionRepresentation;

/**
 * @internal
 *
 * This handler workaround some problems with serialize Representation in specific groups
 */
class RepresentationHandler implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
                'class' => CollectionRepresentation::class,
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
                'class' => PaginatedRepresentation::class,
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
                'class' => ItemCollectionRepresentation::class,
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
                'class' => ListRepresentation::class,
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var RepresentationInterface $representation */
        $representation = $event->getObject();
        $context = $event->getContext();
        $visitor = $event->getVisitor();

        $data = $representation->toArray();

        foreach ($data as $key => $value) {
            $visitor->visitProperty(new StaticPropertyMetadata(get_class($representation), $key, $value), $value);
        }
    }
}
