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

use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Behavior for route (sulu:path) documents.
 */
class RouteSubscriber implements EventSubscriberInterface
{
    const DOCUMENT_HISTORY_FIELD = 'history';

    const NODE_HISTORY_FIELD = 'sulu:history';

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        SessionManagerInterface $sessionManager
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->sessionManager = $sessionManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be exectued before the TargetSubscriber
            Events::PERSIST => ['handlePersist', 5],
            Events::HYDRATE => 'handleHydrate',
            Events::REMOVE => ['handleRemove', 550],
            Events::PUBLISH => 'handlePublish',
        ];
    }

    /**
     * Writes the history status of the node to the document.
     *
     * @param HydrateEvent $event
     */
    public function handleHydrate(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $document->setHistory($event->getNode()->getPropertyValue(self::NODE_HISTORY_FIELD));
    }

    /**
     * Updates the route for the given document and creates history routes if necessary.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $node = $event->getNode();
        $node->setProperty(self::NODE_HISTORY_FIELD, $document->isHistory());

        $targetDocument = $document->getTargetDocument();

        if ($targetDocument instanceof HomeDocument
            || !$targetDocument instanceof WebspaceBehavior
            || !$targetDocument instanceof ResourceSegmentBehavior
        ) {
            return;
        }

        // copy new route to old position
        $webspaceKey = $targetDocument->getWebspaceName();
        $locale = $this->documentInspector->getLocale($document);

        $routePath = $this->sessionManager->getRoutePath($webspaceKey, $locale, null)
            . $targetDocument->getResourceSegment();

        // create a route node if it is not a new document and the path changed
        $documentPath = $this->documentInspector->getPath($document);
        if ($documentPath && $documentPath != $routePath) {
            /** @var RouteDocument $newRouteDocument */
            $newRouteDocument = $this->documentManager->create('route');
            $newRouteDocument->setTargetDocument($targetDocument);
            $this->documentManager->persist(
                $newRouteDocument,
                $locale,
                [
                    'path' => $routePath,
                    'auto_create' => true,
                ]
            );
            $this->documentManager->publish($newRouteDocument, $locale);

            // change routes in old position to history
            $this->changeOldPathToHistoryRoutes($document, $newRouteDocument);
        }
    }

    /**
     * Removes the routes for the given document and removes history routes if necessary.
     *
     * @param RemoveEvent $event
     */
    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $this->recursivelyRemoveRoutes($document);
    }

    /**
     * Handles the history field for the route on publish.
     *
     * @param PublishEvent $event
     */
    public function handlePublish(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof RouteBehavior) {
            return;
        }

        $event->getNode()->setProperty(self::NODE_HISTORY_FIELD, $document->isHistory());
    }

    /**
     * Remove given Route and his history.
     *
     * @param $document
     */
    private function recursivelyRemoveRoutes(RouteBehavior $document)
    {
        $referrers = $this->documentInspector->getReferrers($document);

        foreach ($referrers as $referrer) {
            if (!$referrer instanceof RouteBehavior) {
                continue;
            }

            $this->recursivelyRemoveRoutes($referrer);
            $this->documentManager->remove($referrer);
        }
    }

    /**
     * Changes the old route to a history route and redirect to the new route.
     *
     * @param RouteBehavior $oldDocument
     * @param RouteBehavior $newDocument
     */
    private function changeOldPathToHistoryRoutes(RouteBehavior $oldDocument, RouteBehavior $newDocument)
    {
        $oldDocument->setTargetDocument($newDocument);
        $oldDocument->setHistory(true);
        $oldRouteNode = $this->documentInspector->getNode($oldDocument);
        $oldRouteNode->setProperty(self::NODE_HISTORY_FIELD, true);

        foreach ($this->documentInspector->getReferrers($oldDocument) as $referrer) {
            if ($referrer instanceof RouteBehavior) {
                $referrer->setTargetDocument($newDocument);
                $referrer->setHistory(true);
                $this->documentManager->persist(
                    $referrer,
                    null,
                    [
                        'path' => $this->documentInspector->getPath($referrer),
                    ]
                );
                $this->documentManager->publish($referrer, null);
            }
        }
    }
}
