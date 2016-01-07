<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\PropertyMetadata;
use Sulu\Bundle\WebsiteBundle\Entity\Analytic;

/**
 * Extends analytics serialization process.
 */
class AnalyticSerializeEventSubscriber implements EventSubscriberInterface
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
        $analytic = $event->getObject();

        if (!($analytic instanceof Analytic)) {
            return;
        }

        if ($analytic->getAllDomains()) {
            $metadata = new PropertyMetadata($event->getType()['name'], 'domains');
            $value = new \stdClass();
            $value->domains = true;
            $event->getVisitor()->visitProperty($metadata, $value, $event->getContext());
        }
    }
}
