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

        $visitor = $event->getVisitor();
        $content = $analytics->getContent();

        switch ($analytics->getType()) {
            case 'google':
                $visitor->addData('google_key', $content);
                break;
            case 'google_tag_manager':
                $visitor->addData('google_tag_manager_key', $content);
                break;
            case 'matomo':
                $visitor->addData('matomo_id', $content['siteId']);
                $visitor->addData('matomo_url', $content['url']);
                break;
            case 'custom':
                $visitor->addData('custom_script', $content['value']);
                $visitor->addData('custom_position', $content['position']);
                break;
        }
    }
}
