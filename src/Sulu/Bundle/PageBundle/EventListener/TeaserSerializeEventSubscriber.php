<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\EventListener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * Extends teaser with a uniqueid and media-data.
 */
class TeaserSerializeEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private MediaManagerInterface $mediaManager)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Add uniqueid and media-data to serialized data.
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $teaser = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!($teaser instanceof Teaser)) {
            return;
        }

        $teaserId = \sprintf('%s;%s', $teaser->getType(), $teaser->getId());
        $context->getNavigator()->accept($teaserId);
        $visitor->visitProperty(
            new StaticPropertyMetadata('', 'teaserId', $teaserId),
            $teaserId
        );
    }
}
