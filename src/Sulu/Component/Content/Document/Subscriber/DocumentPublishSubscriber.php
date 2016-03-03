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
     * Documents should be synced if any of the following apply:
     *
     * - They are new
     * - They are synced.
     *
     * @param PersistEvent
     */
    public function handlePersist(PersistEvent $event)
    {
        $node = $event->getNode();

        $emittingManager = $event->getContext()->getDocumentManager();
        $publishManager = $this->registry->getManager($this->publishWorkspaceName);

        // if the  emitting manager is the publish manager, stop we
        // don't want to sync from the publish workspace!
        if ($emittingManager === $publishManager) {
            return;
        }

        $document = $event->getDocument();

        // only sync documents implementing workflows
        if (!$document instanceof WorkflowStageBehavior) {
            return;
        }

        // always sync new documents
        if ($node->isNew()) {
            $this->syncDocument($document);
            return;
        }

        // only sync documents that are published
        if ($document->getWorkflowStage() !== WorkflowStage::PUBLISHED) {
            return;
        }

        $this->syncDocument($document);
    }

    public function handleFlush(FlushEvent $event)
    {
        $context = $event->getContext();
        $emittingManager = $context->getDocumentManager();
        $publishManager = $this->registry->getManager($this->publishWorkspaceName);

        // do not do anything if the default manager and publish manager are the same.
        if ($emittingManager === $publishManager) {
            $this->queue = array();
            return;
        }

        if (empty($this->queue)) {
            return;
        }

        $queue = $this->queue;
        while ($document = array_shift($queue)) {
            if ($document instanceof ResourceSegmentBehavior) {
                $document->setResourceSegment('/' . uniqid());
            }

            $path = $event->getContext()->getInspector()->getPath($document);
            $locale = $event->getContext()->getInspector()->getLocale($document);

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

    private function syncDocument($document)
    {
        $this->queue[] = $document;
    }
}
