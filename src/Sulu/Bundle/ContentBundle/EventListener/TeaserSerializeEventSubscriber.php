<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\EventListener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\ContentBundle\Teaser\Teaser;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * Extends teaser with a uniqueid and media-data.
 */
class TeaserSerializeEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @param MediaManagerInterface $mediaManager
     */
    public function __construct(MediaManagerInterface $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

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
            ],
        ];
    }

    /**
     * Add uniqueid and media-data to serialized data.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $teaser = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if (!($teaser instanceof Teaser)) {
            return;
        }

        $visitor->addData('teaserId', $context->accept(sprintf('%s;%s', $teaser->getType(), $teaser->getId())));
    }
}
