<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class NavigationContextSubscriber extends AbstractMappingSubscriber
{
    const FIELD = 'navContexts';

    public function supports($document)
    {
        return $document instanceof NavigationContextBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(HydrateEvent $event)
    {
        $node = $event->getNode();
        $value = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::FIELD, $event->getLocale()),
            array()
        );
        $event->getDocument()->setNavigationContexts($value);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $node->setProperty(
            $this->encoder->localizedSystemName(self::FIELD, $event->getLocale()),
            $event->getDocument()->getNavigationContexts()
        );
    }
}
