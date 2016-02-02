<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TitleSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    ) {
        $this->encoder = $encoder;
        $this->inspector = $inspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // should happen after content is hydrated
            Events::HYDRATE => ['handleHydrate', -10],
            Events::PERSIST => ['handlePersist', 10],
        ];
    }

    /**
     * @param HydrateEvent $event
     */
    public function handleHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof TitleBehavior) {
            return;
        }

        $title = $this->getTitle($document);

        $document->setTitle($title);
    }

    /**
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof TitleBehavior) {
            return;
        }

        $title = $document->getTitle();

        $structure = $this->inspector->getStructureMetadata($document);
        if (!$structure->hasProperty('title')) {
            return;
        }

        $document->getStructure()->getProperty('title')->setValue($title);
        $this->handleHydrate($event);
    }

    private function getTitle($document)
    {
        if (!$this->hasTitle($document)) {
            return 'Document has no "title" property in content';
        }

        return $document->getStructure()->getProperty('title')->getValue();
    }

    private function hasTitle($document)
    {
        $structure = $this->inspector->getStructureMetadata($document);

        return $structure->hasProperty('title');
    }
}
