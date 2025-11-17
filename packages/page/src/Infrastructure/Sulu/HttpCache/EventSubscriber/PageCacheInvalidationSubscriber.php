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

namespace Sulu\Page\Infrastructure\Sulu\HttpCache\EventSubscriber;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Event\PageWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
class PageCacheInvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?CacheManagerInterface $cacheManager,
        private RouteRepositoryInterface $routeRepository,
        private ContentAggregatorInterface $contentAggregator,
        private RouteGeneratorInterface $routeGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageWorkflowTransitionAppliedEvent::class => 'onWorkflowTransition',
            PageRemovedEvent::class => 'onPageRemoved',
        ];
    }

    public function onWorkflowTransition(PageWorkflowTransitionAppliedEvent $event): void
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

        $page = $event->getPage();
        $uuid = $page->getUuid();

        $this->cacheManager->invalidateTag($uuid);
        $this->invalidatePagePaths($uuid, $event->getResourceLocale());
        $this->invalidatePageExcerpt($page, $event->getResourceLocale());
    }

    public function onPageRemoved(PageRemovedEvent $event): void
    {
        if (!$this->cacheManager) {
            return;
        }

        $this->cacheManager->invalidateTag($event->getResourceId());
    }

    private function invalidatePagePaths(string $uuid, ?string $locale): void
    {
        if (!$locale || !$this->cacheManager) {
            return;
        }

        $routes = $this->routeRepository->findBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => $uuid,
            'locale' => $locale,
        ]);

        foreach ($routes as $route) {
            $url = $this->routeGenerator->generate(
                $route->getSlug(),
                $route->getLocale(),
                $route->getSite(),
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $this->cacheManager->invalidatePath($url);
        }
    }

    private function invalidatePageExcerpt(PageInterface $page, ?string $locale): void
    {
        if (!$locale || !$this->cacheManager) {
            return;
        }

        try {
            /** @var PageDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($page, [
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
