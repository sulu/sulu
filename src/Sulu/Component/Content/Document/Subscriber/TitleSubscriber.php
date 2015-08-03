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

use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;

class TitleSubscriber extends AbstractMappingSubscriber
{
    private $inspector;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    ) {
        parent::__construct($encoder);
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

    public function supports($document)
    {
        return $document instanceof TitleBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        $title = $this->getTitle($document);

        $document->setTitle($title);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        $title = $document->getTitle();

        $structure = $this->inspector->getStructureMetadata($document);
        if (!$structure->hasProperty('title')) {
            return;
        }

        $document->getStructure()->getProperty('title')->setValue($title);
        $this->doHydrate($event);
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
