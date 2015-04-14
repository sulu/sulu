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
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ShadowLocaleSubscriber extends AbstractMappingSubscriber
{
    const SHADOW_ENABLED_FIELD = 'shadow-on';
    const SHADOW_LOCALE_FIELD = 'shadow-base';

    /**
     * {@inheritDoc}
     */
    public function supports($document)
    {
        return $document instanceof ShadowLocaleBehavior;
    }

    /**
     * {@inheritDoc}
     */
    public function doHydrate(HydrateEvent $event)
    {
        $value = $event->getNode()->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $event->getLocale()),
            false
        );
        $event->getDocument()->setShadowLocaleEnabled($value);

        $value = $event->getNode()->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $event->getLocale()),
            null
        );
        $event->getDocument()->setShadowLocale($value);
    }

    /**
     * {@inheritDoc}
     */
    public function doPersist(PersistEvent $event)
    {
        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_ENABLED_FIELD, $event->getLocale()),
            $event->getDocument()->isShadowLocaleEnabled()
        );

        $event->getNode()->setProperty(
            $this->encoder->localizedSystemName(self::SHADOW_LOCALE_FIELD, $event->getLocale()),
            $event->getDocument()->getShadowLocale()
        );
    }
}

