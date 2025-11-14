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
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Snippet\Domain\Event\SnippetRemovedEvent;
use Sulu\Snippet\Domain\Event\SnippetWorkflowTransitionAppliedEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
class SnippetCacheInvalidationSubscriber implements EventSubscriberInterface
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
        private ContentAggregatorInterface $contentAggregator,
        private array $areas
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SnippetWorkflowTransitionAppliedEvent::class => 'onWorkflowTransition',
            SnippetRemovedEvent::class => 'onSnippetRemoved',
        ];
    }

    public function onWorkflowTransition(SnippetWorkflowTransitionAppliedEvent $event): void
    {
        if (!$this->cacheManager) {
            return;
        }

        if (!\in_array($event->getWorkflowTransitionName(), [
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH,
        ])) {
            return;
        }

        $snippet = $event->getSnippet();
        $uuid = $snippet->getUuid();

        $this->cacheManager->invalidateTag($uuid);
        $this->invalidateSnippetExcerpt($snippet, $event->getResourceLocale());
        $this->invalidateSnippetArea($snippet, $event->getResourceLocale());
    }

    private function invalidateSnippetExcerpt(SnippetInterface $snippet, ?string $locale): void
    {
        if (!$locale || !$this->cacheManager) {
            return;
        }

        try {
            /** @var SnippetDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($snippet, [
                'locale' => $locale,
                'stage' => 'live',
            ]);
        } catch (ContentNotFoundException) {
            return;
        }

        foreach ($dimensionContent->getExcerptTags() as $tag) {
            $this->cacheManager->invalidateReference('tag', $tag->getName());
        }

        foreach ($dimensionContent->getExcerptCategoryIds() as $categoryId) {
            $this->cacheManager->invalidateReference('category', (string) $categoryId);
        }
    }

    private function invalidateSnippetArea(SnippetInterface $snippet, ?string $locale): void
    {
        if (!$this->cacheManager || !$locale) {
            return;
        }

        try {
            /** @var SnippetDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($snippet, [
                'locale' => $locale,
                'stage' => 'live',
            ]);
        } catch (ContentNotFoundException) {
            return;
        }

        $templateKey = $dimensionContent->getTemplateKey();
        if (!$templateKey) {
            return;
        }

        foreach ($this->areas as $area) {
            if ($area['template'] !== $templateKey || false === $area['cache-invalidation']) {
                continue;
            }

            $this->cacheManager->invalidateReference('snippet_area', $area['areaKey']);
            break;
        }
    }

    public function onSnippetRemoved(SnippetRemovedEvent $event): void
    {
        if (!$this->cacheManager) {
            return;
        }

        $this->cacheManager->invalidateTag($event->getResourceId());
    }
}
