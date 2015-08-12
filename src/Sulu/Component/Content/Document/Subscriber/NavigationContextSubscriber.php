<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class NavigationContextSubscriber extends AbstractMappingSubscriber
{
    const FIELD = 'navContexts';

    public function supports($document)
    {
        return $document instanceof NavigationContextBehavior;
    }

    /**
     * @param AbstractMappingEvent $event
     */
    protected function doHydrate(AbstractMappingEvent $event)
    {
        $node = $event->getNode();
        $value = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::FIELD, $event->getLocale()),
            []
        );
        $event->getDocument()->setNavigationContexts($value);
    }

    /**
     * @param PersistEvent $event
     */
    protected function doPersist(PersistEvent $event)
    {
        $locale = $event->getLocale();

        if (!$locale) {
            return;
        }

        $node = $event->getNode();
        $node->setProperty(
            $this->encoder->localizedSystemName(self::FIELD, $locale),
            $event->getDocument()->getNavigationContexts() ?: null
        );
    }
}
