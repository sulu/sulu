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

class WorkspaceSyncSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentManagerRegistryInterface
     */
    private $registry;

    /**
     * @var string
     */
    private $liveWorkspaceName;

    /**
     * @var array
     */
    private $queue = array();

    public function __construct(DocumentManagerRegistryInterface $registry, $liveWorkspaceName)
    {
        $this->registry = $registry;
        $this->liveWorkspaceName = $liveWorkspaceName;
    }

    public static function getSubscribedEvents()
    {
        return [
            // persit needs to be before the content mapper subscriber
            // because we need to stop propagation early on the live 
            Events::PERSIST => [ 'handlePersist',10 ],
            Events::FLUSH => 'handleFlush'
        ];
    }

    /**
     * Documents should be synced if any of the following apply:
     *
     * - They are new
     * - They are published.
     *
     * @param PersistEvent
     */
    public function handlePersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();

        $emittingManager = $event->getContext()->getDocumentManager();
        $liveManager = $this->registry->getManager($this->liveWorkspaceName);

        // if the  emitting manager is the live manager, stop we
        // don't want to sync from the live workspace!
        if ($emittingManager === $liveManager) {
            return;
        }

        // always publish new documents
        if ($node->isNew()) {
            $this->syncDocument($document);
            return;
        }

        // only publish documents implementing workflows
        if (!$document instanceof WorkflowStageBehavior) {
            return;
        }

        if ($document->getWorkflowStage() !== WorkflowStage::PUBLISHED) {
            return;
        }

        $this->syncDocument($document);
    }

    public function handleFlush(FlushEvent $event)
    {
        $context = $event->getContext();
        $emittingManager = $context->getDocumentManager();
        $liveManager = $this->registry->getManager($this->liveWorkspaceName);

        // do not do anything if the default manager and live manager are the same.
        if ($emittingManager === $liveManager) {
            $this->queue = array();
            return;
        }

        $queue = $this->queue;
        while ($document = array_shift($queue)) {
            if ($document instanceof ResourceSegmentBehavior) {
                $document->setResourceSegment('/' . uniqid());
            }

            $path = $event->getContext()->getInspector()->getPath($document);
            $locale = $event->getContext()->getInspector()->getLocale($document);

            $liveManager->persist(
                $document,
                $locale,
                [
                    'path' => $path,
                    'auto_create' => true
                ]
            );
        }

        $liveManager->flush();
    }

    private function syncDocument($document)
    {
        $this->queue[] = $document;
    }
}
