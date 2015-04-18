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
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Events;

abstract class AbstractMappingSubscriber implements EventSubscriberInterface
{
    protected $encoder;

    /**
     * @param PropertyEncoder  $encoder
     * @param DocumentAccessor $accessor
     */
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
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->doHydrate($event);
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->doPersist($event);
    }

    /**
     * @param PersistEvent $event
     */
    abstract protected function doPersist(PersistEvent $event);

    /**
     * @param DocumentInterface $document
     */
    abstract protected function supports($document);

    /**
     * @param HydrateEvent $event
     */
    abstract protected function doHydrate(HydrateEvent $event);
}
