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

namespace Sulu\Article\Infrastructure\Sulu\HttpCache\EventSubscriber;

use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionAppliedEvent;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
class ArticleCacheInvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?CacheManagerInterface $cacheManager,
        private RouteRepositoryInterface $routeRepository,
        private ContentAggregatorInterface $contentAggregator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ArticleWorkflowTransitionAppliedEvent::class => 'onWorkflowTransition',
            ArticleRemovedEvent::class => 'onArticleRemoved',
        ];
    }

    public function onWorkflowTransition(ArticleWorkflowTransitionAppliedEvent $event): void
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

        $article = $event->getArticle();
        $uuid = $article->getUuid();

        $this->cacheManager->invalidateTag($uuid);
        $this->invalidateArticlePaths($uuid, $event->getResourceLocale());
        $this->invalidateArticleExcerpt($article, $event->getResourceLocale());
    }

    public function onArticleRemoved(ArticleRemovedEvent $event): void
    {
        if (!$this->cacheManager) {
            return;
        }

        $this->cacheManager->invalidateTag($event->getResourceId());
    }

    private function invalidateArticlePaths(string $uuid, ?string $locale): void
    {
        if (!$locale || !$this->cacheManager) {
            return;
        }

        $routes = $this->routeRepository->findBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => $uuid,
            'locale' => $locale,
        ]);

        foreach ($routes as $route) {
            $this->cacheManager->invalidatePath($route->getSlug());
        }
    }

    private function invalidateArticleExcerpt(ArticleInterface $article, ?string $locale): void
    {
        if (!$locale || !$this->cacheManager) {
            return;
        }

        try {
            /** @var ArticleDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($article, [
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
}
