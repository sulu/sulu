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
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Events;

class ResourceSegmentSubscriber extends AbstractMappingSubscriber
{
    private $inspector;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    )
    {
        parent::__construct($encoder);
        $this->inspector = $inspector;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            // persist should happen before content is mapped
            Events::PERSIST => array('handlePersist', 10),

            // hydrate should happen afterwards
            Events::HYDRATE => array('handleHydrate', -10),
        );
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
        $property = $this->getResourceSegmentProperty($document);
        $segment = $document->getContent()->getProperty($property->getName())->getValue();

        $document->setResourceSegment($segment);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        $property = $this->getResourceSegmentProperty($document);

        $document->getContent()->getProperty(
            $property->getName()
        )->setValue($document->getResourceSegment());
    }

    private function getResourceSegmentProperty($document)
    {
        $structure = $this->inspector->getStructure($document);
        $property = $structure->getPropertyByTagName('sulu.rlp');

        if (!$property) {
            throw new \RuntimeException(sprintf(
                'Structure "%s" does not have a "sulu.rlp" tag which is required for documents implementing the ' .
                'ResourceSegmentBehavior. In "%s"',
                $structure->name,
                $structure->resource
            ));
        }

        return $property;
    }
}
