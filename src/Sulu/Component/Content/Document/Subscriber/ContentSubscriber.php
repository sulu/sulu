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

use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\Event\AbstractDocumentNodeEvent;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ContentSubscriber implements EventSubscriberInterface
{
    private $encoder;

    /**
     * @param PropertyEncoder $encoder
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
            Events::HYDRATE => 'handleHydrate',
            Events::PERSIST => 'handlePersist',
        );
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ContentBehavior) {
            return;
        }

        $node = $event->getNode();
        $value = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName('template', $event->getLocale()),
            null
        );
        $document->setStructureType($value);
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ContentBehavior) {
            return;
        }

        $node = $event->getNode();
        $node->setProperty(
            $this->encoder->localizedSystemName('template', $event->getLocale()),
            $document->getStructureType()
        );
    }
}
