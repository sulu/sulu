<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Subscriber\Behavior;

use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Behavior\TimestampBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;

/**
 * Manage the timestamp (created, changed) fields on
 * documents before they are persisted.
 */
class TimestampSubscriber implements EventSubscriberInterface
{
    const CREATED = 'created';
    const CHANGED = 'changed';

    private $encoder;

    public function __construct(PropertyEncoder $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => 'handlePersist',
            Events::HYDRATE => 'handleHydrate',
        );
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof TimestampBehavior) {
            return;
        }

        $node = $event->getNode();

        if (!$document->getCreated()) {
            $name = $this->encoder->localizedSystemName(self::CREATED, $event->getLocale());
            $node->setProperty($name, new \DateTime());
        }

        $name = $this->encoder->localizedSystemName(self::CHANGED, $event->getLocale());
        $node->setProperty($name, new \DateTime());
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof TimestampBehavior) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();
        $accessor = $event->getAccessor();
        $accessor->set(
            self::CREATED,
            $node->getPropertyValueWithDefault(
                $this->encoder->localizedSystemName(self::CREATED, $locale),
                null
            )
        );

        $accessor->set(
            self::CHANGED,
            $node->getPropertyValueWithDefault(
                $this->encoder->localizedSystemName(self::CHANGED, $locale),
                null
            )
        );
    }
}
