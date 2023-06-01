<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Reference\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\DocumentReferenceProviderInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class DocumentReferenceSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string, DocumentReferenceProviderInterface>
     */
    private array $documentReferenceProviders;

    /**
     * @param iterable<DocumentReferenceProviderInterface> $documentReferenceProviders
     */
    public function __construct(iterable $documentReferenceProviders)
    {
        $this->documentReferenceProviders = $documentReferenceProviders instanceof \Traversable ? \iterator_to_array($documentReferenceProviders) : $documentReferenceProviders;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PUBLISH => 'onPublish',
            Events::PERSIST => 'onPersist',
            Events::REMOVE => 'onRemove',
        ];
    }

    public function onPublish(PublishEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof StructureBehavior
            || !$document instanceof TitleBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        $this->getProvider($document)?->updateReferences($document, $locale);
    }

    public function onPersist(PersistEvent $event): void
    {
        $document = $event->getDocument();
        $locale = $event->getLocale();

        if (!$document instanceof StructureBehavior
            || !$document instanceof TitleBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        $this->getProvider($document)?->updateReferences($document, $locale);
    }

    public function onRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();

        if (!$document instanceof StructureBehavior
            || !$document instanceof UuidBehavior
        ) {
            return;
        }

        $this->getProvider($document)?->removeReferences($document);
    }

    private function getProvider(StructureBehavior $document): ?DocumentReferenceProviderInterface
    {
        $documentResourcesKey = defined(get_class($document) . '::RESOURCE_KEY')
            // @phpstan-ignore-next-line PHPStan does not detect the `defined` call
            ? $document::RESOURCE_KEY
            : '';

        return $this->documentReferenceProviders[$documentResourcesKey] ?? null;
    }
}
