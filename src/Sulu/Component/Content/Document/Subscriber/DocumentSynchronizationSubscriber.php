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
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Document\SynchronizationManager;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\DocumentManagerContext;

class DocumentSynchronizationSubscriber implements EventSubscriberInterface
{
    /**
     * @var SynchronizationManager
     */
    private $syncManager;

    /**
     * @var DocumentManagerInterface
     */
    private $defaultManager;

    /**
     * @var object[]
     */
    private $persistQueue = [];

    /**
     * @var object[]
     */
    private $removeQueue = [];

    /**
     * NOTE: We pass the default manager here because we need to ensure that we
     *       only process documents FROM the default manager. If we could assign
     *       event subscribers to specific document managers this would not
     *       be necessary.
     */
    public function __construct(DocumentManagerInterface $defaultManager, SynchronizationManager $syncManager)
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
            Events::REMOVE => 'handleRemove',
            Events::METADATA_LOAD => 'handleMetadataLoad',

            // persist needs to be before the content mapper subscriber
            // because we need to stop propagation early on the publish 
            Events::PERSIST => [ 'handlePersist',10 ],
        ];
    }

    public function handleMetadataLoad(MetadataLoadEvent $event)
    {
        $metadata = $event->getMetadata();

        if (false === $metadata->getReflectionClass()->isSubclassOf(SynchronizeBehavior::class)) {
            return;
        }

        $encoding = $metadata->getReflectionClass()->isSubclassOf(LocaleBehavior::class) ? 'system_localized' : 'system';

        $metadata->addFieldMapping('synchronizedManagers', [
            'encoding' => $encoding,
            'property' => SynchronizeBehavior::SYNCED_FIELD,
            'type' => 'string',
        ]);
    }

    /**
     * Synchronize new documents with the publish document manager.
     *
     * @param PersistEvent
     */
    public function handlePersist(PersistEvent $event)
    {
        $context = $event->getContext();

        if (false === $this->isEmittingManagerDefaultManager($context)) {
            return;
        }
        // now we now deal only with the DEFAULT manager...

        $document = $event->getDocument();

        // only sync documents implementing the sync behavior
        if (!$document instanceof SynchronizeBehavior) {
            return;
        }

        // only sync new documents automatically
        if (false === $event->getNode()->isNew()) {
            // otherwise the node is now capable of synchronized with all the managers.
            $event->getContext()
                ->getMetadataFactory()
                ->getMetadataForClass(get_class($document))
                ->setFieldValue($document, 'synchronizedManagers', []);
            return;
        }

        $inspector = $this->defaultManager->getInspector();
        $locale = $inspector->getLocale($document);
        $this->persistQueue[] = [
            'document' => $document,
            'locale' => $locale
        ];
    }

    public function handleRemove(RemoveEvent $removeEvent)
    {
        $context = $removeEvent->getContext();
        if (false === $this->isEmittingManagerDefaultManager($context)) {
            return;
        }

        $document = $removeEvent->getDocument();

        // only sync documents implementing the sync behavior
        if (!$document instanceof SynchronizeBehavior) {
            return;
        }

        $this->removeQueue[] = $document;
    }


    public function handleFlush(FlushEvent $event)
    {
        if (empty($this->persistQueue) && empty($this->removeQueue)) {
            return;
        }

        $context = $event->getContext();

        if (false === $this->isEmittingManagerDefaultManager($context)) {
            return;
        }

        $publishManager = $this->syncManager->getPublishDocumentManager();
        $defaultFlush = false;

        // process the persistQueue, FIFO (first in, first out)
        // array_shift will return and remove the first element of
        // the persistQueue for each iteration.
        while ($entry = array_shift($this->persistQueue)) {
            $defaultFlush = true;
            $document = $entry['document'];
            $locale = $entry['locale'];

            // we need to load the document in the locale it was persisted in.
            // note that this should not create any significant overhead as all
            // the data is already in-memory.
            $inspector = $this->defaultManager->getInspector();
            $this->defaultManager->find($inspector->getUUid($document), $locale);

            // delegate to the sync manager to synchronize the document.
            $this->syncManager->synchronizeSingle($document);
        }

        while ($entry = array_shift($this->removeQueue)) {
            $publishManager->remove($entry);
        }

        // flush both managers. the publish manager will then commit
        // the synchronized documents and the default manager will update
        // the "synchronized document managers" field of original documents.
        $publishManager->flush();

        // only flush the default manager when objects have been synchronized (
        // not removed).
        if ($defaultFlush) {
            $this->defaultManager->flush();
        }
    }

    private function isEmittingManagerDefaultManager(DocumentManagerContext $context)
    {
        $emittingManager = $context->getDocumentManager();

        // do nothing, see same condition in handlePersist.
        if ($emittingManager === $this->defaultManager) {
            return true;
        }

        return false;
    }
}
