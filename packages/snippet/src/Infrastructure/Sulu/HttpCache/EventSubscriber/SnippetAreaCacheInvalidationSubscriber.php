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

namespace Sulu\Snippet\Infrastructure\Sulu\HttpCache\EventSubscriber;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Snippet\Domain\Event\SnippetAreaModifiedEvent;
use Sulu\Snippet\Domain\Event\SnippetAreaRemovedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
class SnippetAreaCacheInvalidationSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<string, array{
     *     areaKey: string,
     *     template: string,
     *     cache-invalidation: bool
     * }> $areas
     */
    public function __construct(
        private ?CacheManagerInterface $cacheManager,
        private array $areas
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SnippetAreaModifiedEvent::class => 'invalidateSnippetAreaOnModified',
            SnippetAreaRemovedEvent::class => 'invalidateSnippetAreaOnRemoved',
        ];
    }

    public function invalidateSnippetAreaOnModified(SnippetAreaModifiedEvent $event): void
    {
        $this->invalidateSnippetArea($event->getSnippetArea()->getAreaKey());
    }

    public function invalidateSnippetAreaOnRemoved(SnippetAreaRemovedEvent $event): void
    {
        $this->invalidateSnippetArea((string) $event->getAreaKey());
    }

    private function invalidateSnippetArea(string $areaKey): void
    {
        if (!$this->cacheManager) {
            return;
        }

        $area = $this->areas[$areaKey] ?? null;

        if (null === $area || false === $area['cache-invalidation']) {
            return;
        }

        $this->cacheManager->invalidateReference('snippet_area', $areaKey);
    }
}
