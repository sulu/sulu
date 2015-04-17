<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;

/**
 * Recursively remove paths
 *
 * Note that this is only here for BC purposes
 */
class RouteRemoveSubscriber implements EventSubscriberInterface
{
    private $inspector;
    private $documentManager;

    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $inspector
    )
    {
        $this->documentManager = $documentManager;
        $this->inspector = $inspector;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::REMOVE => array('handleRemove', 550),
        );
    }

    public function handleRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        // TODO: This is not a good indicator. There should be a RoutableBehavior here.
        if (!$document instanceof ContentBehavior) {
            return;
        }

        $this->recursivelyRemoveRoutes($document);
    }

    private function recursivelyRemoveRoutes($document)
    {
        $referrers = $this->inspector->getReferrers($document);

        foreach ($referrers as $document) {
            if (!$document instanceof RouteDocument) {
                continue;
            }

            $this->recursivelyRemoveRoutes($document);
            $this->documentManager->remove($document);
        }
    }
}
