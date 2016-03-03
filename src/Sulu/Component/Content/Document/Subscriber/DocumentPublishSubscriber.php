<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\DocumentManagerRegistryInterface;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;

class DocumentPublishSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentManagerRegistryInterface
     */
    private $registry;

    /**
     * @var string
     */
    private $publishWorkspaceName;

    /**
     * @var array
     */
    private $queue = array();

    public function __construct(DocumentManagerRegistry $registry, $publishWorkspaceName)
    {
        $this->registry = $registry;
        $this->publishWorkspaceName = $publishWorkspaceName;
    }

    public static function getSubscribedEvents()
    {
        return [
            // persit needs to be before the content mapper subscriber
            // because we need to stop propagation early on the publish 
            Events::PERSIST => [ 'handlePersist',10 ],
            Events::FLUSH => 'handleFlush'
        ];
    }

    /**
     * Sync documents to the other workspace if they implement
     * WorkflowStageBehavior and:
     *
     *   A. They are new.
     *   B. They are published.
     *
     * @param PersistEvent
     */
    public function handlePersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $context = $event->getContext();

        $emittingManager = $context->getDocumentManager();
        $publishManager = $this->registry->getManager($this->publishWorkspaceName);

        // if the  emitting manager is the publish manager, stop we
        // don't want to sync from the publish workspace!
        //
        // Note: this should be removed by implementing per-document-manager
        // event-subscribers.
        if ($publishManager === $emittingManager) {
            return;
        }

        $document = $event->getDocument();

        // only sync documents implementing workflows
        if (!$document instanceof WorkflowStageBehavior) {
            return;
        }

        $locale = $context->getInspector()->getLocale($document);

        // always sync new documents
        if ($node->isNew()) {
            $this->queueDocument($document, $locale);
            return;
        }

        // only sync documents that are published
        if ($document->getWorkflowStage() !== WorkflowStage::PUBLISHED) {
            return;
        }

        $this->queueDocument($document, $locale);
    }

    public function handleFlush(FlushEvent $event)
    {
        $context = $event->getContext();
        $emittingManager = $context->getDocumentManager();
        $publishManager = $this->registry->getManager($this->publishWorkspaceName);

        // do nothing, see same condition in handlePersist.
        if ($publishManager === $emittingManager) {
            return;
        }

        if (empty($this->queue)) {
            return;
        }

        while ($entry = array_shift($this->queue)) {
            $document = $entry['document'];

            // this is a temporary (and invalid) hack until the routing system
            // is converted to use the document manager.
            if ($document instanceof ResourceSegmentBehavior) {
                $document->setResourceSegment('/' . uniqid());
            }

            $path = $event->getContext()->getInspector()->getPath($document);
            $locale = $entry['locale'];

            $publishManager->persist(
                $document,
                $locale,
                [
                    'path' => $path,
                    'auto_create' => true
                ]
            );
        }

        $publishManager->flush();
    }

    private function queueDocument($document, $locale)
    {
        $this->queue[] = [
            'document' => $document,
            'locale' => $locale
        ];
    }
}
