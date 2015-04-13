<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Sulu\Component\Content\Document\Property\PropertyContainer;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Component\DocumentManager\ClassNameInflector;

/**
 * Normalize proxy object class names
 */
class ProxySubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
            ),
        );
    }

    /**
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof LazyLoadingInterface) {
            $event->setType(ClassNameInflector::getUserClassName(get_class($object)));
        }
    }
}
;

