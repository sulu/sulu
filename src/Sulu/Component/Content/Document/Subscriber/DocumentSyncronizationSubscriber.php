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
use Sulu\Component\Content\Document\Behavior\SyncronizeBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Document\SyncronizationManager;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;

class DocumentSyncronizationSubscriber implements EventSubscriberInterface
{
    /**
     * @var SyncronizationManager
     */
    private $syncManager;

    /**
     * @var DocumentManagerInterface
     */
    private $defaultManager;

    /**
     * NOTE: We pass the default manager here because we need to ensure that we
     *       only process documents FROM the default manager. If we could assign
     *       event subscribers to specific document managers this would not
     *       be necessary.
     */
    public function __construct(DocumentManagerInterface $defaultManager, SyncronizationManager $syncManager)
    {
        $this->defaultManager = $defaultManager;
        $this->syncManager = $syncManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::FLUSH => 'handleFlush',
            Events::METADATA_LOAD => 'handleMetadataLoad',

            // persist needs to be before the content mapper subscriber
            // because we need to stop propagation early on the publish 
            Events::PERSIST => [ 'handlePersist',10 ],
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(SyncronizeBehavior::class)) {
            return;
        }

        $metadata->addFieldMapping('syncronizedManagers', [
            'encoding' => 'system_localized',
            'property' => SyncronizeBehavior::SYNCED_FIELD,
            'type' => 'string',
        ]);
    }

    /**
     * Syncronize new documents with the publish document manager.
     *
     * @param PersistEvent
     */
    public function handlePersist(PersistEvent $event)
    {
        $context = $event->getContext();

        $emittingManager = $context->getDocumentManager();

        // if the  emitting manager is not the default manager, then
        // return - see note in constructor.
        if ($emittingManager !== $this->defaultManager) {
            return;
        }
        // now we now deal only with the DEFAULT manager...

        $document = $event->getDocument();

        // only sync documents implementing the sync behavior
        if (!$document instanceof SyncronizeBehavior) {
            return;
        }

        $inspector = $emittingManager->getInspector();

        // only sync new documents automatically
        if (false === $event->getNode()->isNew()) {
            return;
        }

        $locale = $inspector->getLocale($document);
        $this->queue[] = [
            'document' => $document,
            'locale' => $locale
        ];
    }

    public function handleFlush(FlushEvent $event)
    {
        if (empty($this->queue)) {
            return;
        }

        $context = $event->getContext();
        $publishManager = $this->syncManager->getPublishDocumentManager();
        $emittingManager = $context->getDocumentManager();

        // do nothing, see same condition in handlePersist.
        if ($emittingManager !== $this->defaultManager) {
            return;
        }

        // process the queue, FIFO (first in, first out)
        // array_shift will return and remove the first element of
        // the queue for each iteration.
        while ($entry = array_shift($this->queue)) {
            $document = $entry['document'];
            $locale = $entry['locale'];

            // we need to load the document in the locale it was persisted in.
            // note that this should not create any significant overhead as all
            // the data is already in-memory.
            //
            // TODO: This, currently causes an exception:
            //
            //      Document 
            //      "ProxyManagerGeneratedProxy\__PM__\Sulu\Component\DocumentManager\Document\UnknownDocument\Generateda84aebfffbf882fd8bddc950faa89e05"
            //      with OID "00000000523d  b63f000000001e49b225" is not
            //      managed, there are "0" managed objects, 
            //
            // $this->defaultManager->find($document->getUUid(), $locale);

            // delegate to the sync manager to syncronize the document.
            $this->syncManager->syncronizeSingle($document);
        }

        // flush both managers. the publish manager will then commit
        // the syncronized documents and the default manager will update
        // the "syncronized document managers" field of original documents.
        $publishManager->flush();
        $this->defaultManager->flush();
    }
}
