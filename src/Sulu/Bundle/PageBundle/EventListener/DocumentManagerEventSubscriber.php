<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\EventListener;

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Domain\Event\PageChildrenReorderedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageCopiedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageCreatedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageDraftRemovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageLocaleAddedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageLocaleRemovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageModifiedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageMovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PagePublishedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageRemovedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageUnpublishedEvent;
use Sulu\Bundle\PageBundle\Domain\Event\PageVersionRestoredEvent;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

class DocumentManagerEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentDomainEventCollectorInterface
     */
    private $domainEventCollector;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var DomainEvent[]
     */
    private $eventsToBeDispatchedAfterFlush = [];

    /**
     * @var array<string, bool>
     */
    private $persistEventsWithNewDocument = [];

    /**
     * @var array<string, bool>
     */
    private $persistEventsWithNewLocale = [];

    public function __construct(
        DocumentDomainEventCollectorInterface $domainEventCollector,
        DocumentManagerInterface $documentManager,
        PropertyEncoder $propertyEncoder
    ) {
        $this->domainEventCollector = $domainEventCollector;
        $this->documentManager = $documentManager;
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::CONFIGURE_OPTIONS => 'handleConfigureOptions',
            Events::FLUSH => 'handleFlush',
            Events::PERSIST => [
                ['handlePrePersist', 479], // Priority needs to be lower than AutoNameSubscriber::handlePersist (480)
                ['handlePersist', -10000],
            ],
            Events::REMOVE => ['handleRemove', -10000],
            Events::REMOVE_LOCALE => ['handleRemoveLocale', -10000],
            Events::COPY => ['handleCopy', -10000],
            Events::MOVE => ['handleMove', -10000],
            Events::PUBLISH => ['handlePublish', -10000],
            Events::UNPUBLISH => ['handleUnpublish', -10000],
            Events::REMOVE_DRAFT => ['handleRemoveDraft', -10000],
            Events::RESTORE => ['handleRestore', -10000],
            Events::REORDER => ['handleReorder', -10000],
        ];
    }

    public function handleConfigureOptions(ConfigureOptionsEvent $event): void
    {
        $options = $event->getOptions();
        $options->setDefaults(
            [
                'omit_modified_domain_event' => false,
            ]
        );
        $options->setAllowedTypes('omit_modified_domain_event', 'bool');
    }

    public function handleFlush(FlushEvent $event): void
    {
        $eventsToBeDispatched = $this->eventsToBeDispatchedAfterFlush;

        if (0 === \count($eventsToBeDispatched)) {
            return;
        }

        $this->eventsToBeDispatchedAfterFlush = [];

        foreach ($eventsToBeDispatched as $eventConfig) {
            $type = $eventConfig['type'] ?? null;
            $options = $eventConfig['options'] ?? [];

            switch ($type) {
                case PageCopiedEvent::class:
                    $pagePath = $options['pagePath'] ?? null;
                    Assert::notNull($pagePath);
                    $locale = $options['locale'] ?? null;
                    Assert::notNull($locale);
                    $copiedPageId = $options['copiedPageId'] ?? null;
                    Assert::notNull($copiedPageId);
                    $copiedPageWebspaceKey = $options['copiedPageWebspaceKey'] ?? null;
                    Assert::notNull($copiedPageWebspaceKey);
                    $copiedPageTitle = $options['copiedPageTitle'] ?? null;
                    Assert::notNull($copiedPageTitle);

                    /** @var BasePageDocument $document */
                    $document = $this->documentManager->find($pagePath, $locale);

                    $this->domainEventCollector->collect(
                        new PageCopiedEvent(
                            $document,
                            $copiedPageId,
                            $copiedPageWebspaceKey,
                            $copiedPageTitle,
                            $locale
                        )
                    );

                    $this->documentManager->flush();

                    break;
            }
        }
    }

    public function handlePrePersist(PersistEvent $event): void
    {
        if (!$event->hasNode()) {
            return;
        }

        $node = $event->getNode();
        $locale = $event->getLocale();

        $eventHash = \spl_object_hash($event);

        if ($this->isNewNode($node)) {
            $this->persistEventsWithNewDocument[$eventHash] = true;

            return;
        }

        if ($this->isNewLocale($node, $locale)) {
            $this->persistEventsWithNewLocale[$eventHash] = true;
        }
    }

    public function handlePersist(PersistEvent $event): void
    {
        if (true === $event->getOption('omit_modified_domain_event')) {
            return;
        }

        $node = $event->getNode();
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $payload = $this->getPayloadFromPageDocument($document);

        $eventHash = \spl_object_hash($event);

        if (true === ($this->persistEventsWithNewDocument[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewDocument[$eventHash]);

            $this->domainEventCollector->collect(
                new PageCreatedEvent($document, $locale, $payload)
            );

            return;
        }

        if (true === ($this->persistEventsWithNewLocale[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewLocale[$eventHash]);

            $this->domainEventCollector->collect(
                new PageLocaleAddedEvent($document, $locale, $payload)
            );

            return;
        }

        $this->domainEventCollector->collect(
            new PageModifiedEvent($document, $locale, $payload)
        );
    }

    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageRemovedEvent(
                $document->getUuid(),
                $document->getWebspaceName(),
                $document->getTitle(),
                $document->getLocale()
            )
        );
    }

    public function handleRemoveLocale(RemoveLocaleEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageLocaleRemovedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleCopy(CopyEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->eventsToBeDispatchedAfterFlush[] = [
            'type' => PageCopiedEvent::class,
            'options' => [
                'pagePath' => $event->getCopiedPath(),
                'locale' => $document->getLocale(),
                'copiedPageId' => $document->getUuid(),
                'copiedPageWebspaceKey' => $document->getWebspaceName(),
                'copiedPageTitle' => $document->getTitle(),
            ],
        ];
    }

    public function handleMove(MoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageMovedEvent(
                $document,
                $event->getDestId()
            )
        );
    }

    public function handlePublish(PublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PagePublishedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleUnpublish(UnpublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageUnpublishedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleRemoveDraft(RemoveDraftEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageDraftRemovedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleRestore(RestoreEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();
        $version = $event->getVersion();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageVersionRestoredEvent(
                $document,
                $locale,
                $version
            )
        );
    }

    public function handleReorder(ReorderEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof PageDocument) {
            return;
        }

        $parentDocument = $document->getParent();

        if (!$document instanceof BasePageDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageChildrenReorderedEvent(
                $parentDocument
            )
        );
    }

    private function getPayloadFromPageDocument(BasePageDocument $pageDocument): array
    {
        $data = $pageDocument->getStructure()->toArray();
        $data['ext'] = $pageDocument->getExtensionsData()->toArray();

        return $data;
    }

    /**
     * @see SecuritySubscriber::handlePersistCreate()
     */
    private function isNewNode(NodeInterface $node): bool
    {
        $properties = $node->getProperties(
            $this->propertyEncoder->encode(
                'system_localized',
                StructureSubscriber::STRUCTURE_TYPE_FIELD,
                '*'
            )
        );

        return 0 === \count($properties);
    }

    private function isNewLocale(NodeInterface $node, string $locale): bool
    {
        $localizedProperties = $node->getProperties(
            $this->propertyEncoder->localizedContentName('*', $locale)
        );

        return 0 === \count($localizedProperties);
    }
}
