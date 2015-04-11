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
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ResourceSegmentSubscriber extends AbstractMappingSubscriber
{
    private $inspector;

    public function __construct(
        DocumentInspector $inspector
    )
    {
        $this->inspector = $inspector;
    }

    public function supports($document)
    {
        return $document instanceof ResourceSegmentBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        $structure = $this->inspector->getStructure($document);
        $property = $structure->getPropertyByTagName('sulu.rlp');
        var_dump($property);die();;

        $node = $event->getNode();
        $value = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName(self::URL_FIELD, $event->getLocale()),
            null
        );
        $event->getDocument()->setResourceSegment($value);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $node->setProperty(
            $this->encoder->localizedSystemName(self::URL_FIELD, $event->getLocale()),
            $event->getDocument()->getResourceSegment()
        );
    }
}
