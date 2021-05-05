<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\EventListener;

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetCopiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetCreatedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetModifiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetRemovedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetTranslationAddedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetTranslationCopiedEvent;
use Sulu\Bundle\SnippetBundle\Domain\Event\SnippetTranslationRemovedEvent;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\CopyLocaleEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\RemoveLocaleEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
class DomainEventSubscriber implements EventSubscriberInterface
{
    const TITLE_FIELD = 'title';

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
     * @var array<array<string, mixed>>
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
            Events::FLUSH => 'handleFlush',
            Events::PERSIST => [
                ['handlePrePersist', 479], // Priority needs to be lower than AutoNameSubscriber::handlePersist (480)
                ['handlePersist', -10000],
            ],
            Events::REMOVE => ['handleRemove', -10000],
            Events::REMOVE_LOCALE => ['handleRemoveLocale', -10000],
            Events::COPY_LOCALE => ['handleCopyLocale', -10000],
            Events::COPY => ['handleCopy', -10000],
        ];
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
                case SnippetCopiedEvent::class:
                    $snippetPath = $options['snippetPath'] ?? null;
                    Assert::notNull($snippetPath);
                    $locale = $options['locale'] ?? null;
                    Assert::notNull($locale);
                    $sourceSnippetId = $options['sourceSnippetId'] ?? null;
                    Assert::notNull($sourceSnippetId);
                    $sourceSnippetTitle = $options['sourceSnippetTitle'] ?? null;
                    Assert::notNull($sourceSnippetTitle);

                    /** @var SnippetDocument $document */
                    $document = $this->documentManager->find($snippetPath, $locale);

                    $this->domainEventCollector->collect(
                        new SnippetCopiedEvent(
                            $document,
                            $sourceSnippetId,
                            $sourceSnippetTitle,
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

        /** @var string|null $locale */
        $locale = $event->getLocale();
        $node = $event->getNode();

        if (null === $locale) {
            return;
        }

        $eventHash = \spl_object_hash($event);

        if ($this->isNewNode($node)) {
            $this->persistEventsWithNewDocument[$eventHash] = true;

            return;
        }

        if ($this->isNewTranslation($node, $locale)) {
            $this->persistEventsWithNewLocale[$eventHash] = true;
        }
    }

    public function handlePersist(PersistEvent $event): void
    {
        if (true === $event->getOption('omit_modified_domain_event')) {
            return;
        }

        /** @var string|null $locale */
        $locale = $event->getLocale();
        $document = $event->getDocument();

        if (null === $locale || !$document instanceof SnippetDocument) {
            return;
        }

        $payload = $this->getPayloadFromSnippetDocument($document);

        $eventHash = \spl_object_hash($event);

        if (true === ($this->persistEventsWithNewDocument[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewDocument[$eventHash]);

            $this->domainEventCollector->collect(
                new SnippetCreatedEvent($document, $locale, $payload)
            );

            return;
        }

        if (true === ($this->persistEventsWithNewLocale[$eventHash] ?? null)) {
            unset($this->persistEventsWithNewLocale[$eventHash]);

            $this->domainEventCollector->collect(
                new SnippetTranslationAddedEvent($document, $locale, $payload)
            );

            return;
        }

        $this->domainEventCollector->collect(
            new SnippetModifiedEvent($document, $locale, $payload)
        );
    }

    public function handleRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof SnippetDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new SnippetRemovedEvent(
                $document->getUuid(),
                $document->getTitle(),
                $document->getLocale()
            )
        );
    }

    public function handleRemoveLocale(RemoveLocaleEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof SnippetDocument) {
            return;
        }

        $this->domainEventCollector->collect(
            new SnippetTranslationRemovedEvent(
                $document,
                $locale
            )
        );
    }

    public function handleCopyLocale(CopyLocaleEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof SnippetDocument) {
            return;
        }

        $destDocument = $event->getDestDocument();

        if (!$destDocument instanceof SnippetDocument) {
            return;
        }

        $destLocale = $event->getDestLocale();
        $sourceLocale = $event->getLocale();
        $payload = $this->getPayloadFromSnippetDocument($destDocument);

        $this->domainEventCollector->collect(
            new SnippetTranslationCopiedEvent(
                $destDocument,
                $destLocale,
                $sourceLocale,
                $payload
            )
        );
    }

    public function handleCopy(CopyEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof SnippetDocument) {
            return;
        }

        $this->eventsToBeDispatchedAfterFlush[] = [
            'type' => SnippetCopiedEvent::class,
            'options' => [
                'snippetPath' => $event->getCopiedPath(),
                'locale' => $document->getLocale(),
                'sourceSnippetId' => $document->getUuid(),
                'sourceSnippetTitle' => $document->getTitle(),
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    private function getPayloadFromSnippetDocument(SnippetDocument $snippetDocument): array
    {
        $data = $snippetDocument->getStructure()->toArray();

        /** @var ExtensionContainer|mixed[] $extensionData */
        $extensionData = $snippetDocument->getExtensionsData();

        if ($extensionData instanceof ExtensionContainer) {
            $extensionData = $extensionData->toArray();
        }

        $data['ext'] = $extensionData;

        return $data;
    }

    /**
     * @param NodeInterface<mixed> $node
     */
    private function isNewNode(NodeInterface $node): bool
    {
        /** @var \Countable $properties */
        $properties = $node->getProperties(
            $this->propertyEncoder->encode(
                'system_localized',
                self::TITLE_FIELD,
                '*'
            )
        );

        return 0 === \count($properties);
    }

    /**
     * @param NodeInterface<mixed> $node
     */
    private function isNewTranslation(NodeInterface $node, string $locale): bool
    {
        /** @var \Countable $localizedProperties */
        $localizedProperties = $node->getProperties(
            $this->propertyEncoder->localizedContentName('*', $locale)
        );

        return 0 === \count($localizedProperties);
    }
}
