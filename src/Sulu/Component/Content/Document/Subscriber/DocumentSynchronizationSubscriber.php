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

use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\Content\Document\SynchronizationManager;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            Events::PERSIST => ['handlePersist', 10],
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
        $manager = $event->getManager();

        // do not do anything if the default and publish managers are the same.
        if ($this->defaultManager === $this->syncManager->getPublishDocumentManager()) {
            return;
        }

        $this->assertEmittingManagerDefaultManager($manager);

        $document = $event->getDocument();

        // only sync documents implementing the sync behavior
        if (!$document instanceof SynchronizeBehavior) {
            return;
        }

        // only sync new documents automatically
        if (false === $event->getNode()->isNew()) {
            // otherwise the node is now capable of synchronized with all the managers.
            $event->getManager()
                ->getMetadataFactory()
                ->getMetadataForClass(get_class($document))
                ->setFieldValue($document, 'synchronizedManagers', []);

            return;
        }

        $inspector = $this->defaultManager->getInspector();
        $locale = $inspector->getLocale($document);
        $this->persistQueue[] = [
            'document' => $document,
            'locale' => $locale,
        ];
    }

    public function handleRemove(RemoveEvent $removeEvent)
    {
        $manager = $removeEvent->getManager();

        $this->assertEmittingManagerDefaultManager($manager);

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

        if ($this->defaultManager === $this->syncManager->getPublishDocumentManager()) {
            return;
        }

        $manager = $event->getManager();
        $this->assertEmittingManagerDefaultManager($manager);

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

            if ($inspector->getLocale($document) !== $locale) {
                $this->defaultManager->find($inspector->getUUid($document), $locale);
            }

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

    private function assertEmittingManagerDefaultManager(DocumentManagerInterface $manager)
    {
        // do nothing, see same condition in handlePersist.
        if ($manager === $this->defaultManager) {
            return;
        }

        throw new \RuntimeException(
            'The document syncronization subscriber must only be registered to the default document manager'
        );
    }
}
