<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventSubscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\WebsiteBundle\Entity\Analytics;

/**
 * Extends analytics serialization process.
 */
class AnalyticsSerializeEventSubscriber implements EventSubscriberInterface
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
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $analytics = $event->getObject();

        if (!($analytics instanceof Analytics)) {
            return;
        }

        // domains can be an array or a boolean, this difference is necessary for the datagrid to recognize if it is
        // valid for all domains or only a single one
        $domains = $analytics->getDomains();
        if ($analytics->isAllDomains()) {
            $domains = true;
        }

        $event->getVisitor()->addData('domains', $event->getContext()->accept($domains));

        // the content will be appended dynamically because the metadata changes from string to array
        // depended on the type of analytics.
        // see issue: https://github.com/sulu/sulu/issues/3088
        $content = $analytics->getContent();
        if (!is_string($content)) {
            $content = $event->getContext()->accept($content);
        }
        $event->getVisitor()->addData('content', $content);
    }
}
