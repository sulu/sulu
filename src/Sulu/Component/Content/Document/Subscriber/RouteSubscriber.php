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

use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Cmf\Component\RoutingAuto\AutoRouteManager;
use Symfony\Cmf\Component\RoutingAuto\UriContextCollection;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepository;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\DocumentManager\DocumentManager;

/**
 * Behavior for route (sulu:path) documents.
 */
class RouteSubscriber implements EventSubscriberInterface
{
    const DOCUMENT_TARGET_FIELD = 'content';

    private $autoRouteManager;
    private $documentManager;

    /**
     * @var RouteBehavior[]
     */
    private $pending = array();

    public static function getSubscribedEvents()
    {
        return [
            Events::METADATA_LOAD => 'handleMetadataLoad',
            Events::PERSIST => 'handlePersist',
            Events::FLUSH => ['handleFlush', 100]
        ];
    }

    public function __construct(
        AutoRouteManager $autoRouteManager,
        DocumentManager $documentManager
    )
    {
        $this->autoRouteManager = $autoRouteManager;
        $this->documentManager = $documentManager;
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(RouteBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('targetDocument', [
            'encoding' => 'system',
            'property' => self::DOCUMENT_TARGET_FIELD,
            'type' => 'reference',
        ]);
    }

    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof ResourceSegmentBehavior) {
            return;
        }

        $this->pending[] = $document;
    }

    public function handleFlush()
    {
        foreach ($this->pending as $document) {
            $collection = new UriContextCollection($document);
            $this->autoRouteManager->buildUriContextCollection($collection);
        }

        if (!empty($this->pending)) {
            $this->pending = array();
            $this->documentManager->flush();
        }
    }
}
