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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\HttpCache\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Event\PageWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\HttpCache\EventSubscriber\PageCacheInvalidationSubscriber;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageCacheInvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CacheManagerInterface>
     */
    private ObjectProphecy $cacheManager;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private ObjectProphecy $contentAggregator;

    /**
     * @var ObjectProphecy<RouteGeneratorInterface>
     */
    private ObjectProphecy $routeGenerator;

    private PageCacheInvalidationSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->cacheManager = $this->prophesize(CacheManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);

        $this->subscriber = new PageCacheInvalidationSubscriber(
            $this->cacheManager->reveal(),
            $this->routeRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->routeGenerator->reveal()
        );
    }

    public function testInvalidateTagOnPublish(): void
    {
        $page = new Page('page-uuid-123');
        $page->setWebspaceKey('sulu_io');

        $event = new PageWorkflowTransitionAppliedEvent(
            $page,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => 'page-uuid-123',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->cacheManager->invalidateTag('page-uuid-123')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidatePathsOnPublish(): void
    {
        $page = new Page('page-uuid-123');
        $page->setWebspaceKey('sulu_io');

        $event = new PageWorkflowTransitionAppliedEvent(
            $page,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $route1 = new Route(PageInterface::RESOURCE_KEY, 'page-uuid-123', 'en', '/en/test-page');
        $route2 = new Route(PageInterface::RESOURCE_KEY, 'page-uuid-123', 'en', '/en/old-url');

        $this->routeRepository->findBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => 'page-uuid-123',
            'locale' => 'en',
        ])->willReturn([$route1, $route2]);

        $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->routeGenerator->generate('/en/test-page', 'en', null, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/en/test-page');

        $this->routeGenerator->generate('/en/old-url', 'en', null, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/en/old-url');

        $this->cacheManager->invalidateTag('page-uuid-123')
            ->shouldBeCalled();
        $this->cacheManager->invalidatePath('https://example.com/en/test-page')
            ->shouldBeCalled();
        $this->cacheManager->invalidatePath('https://example.com/en/old-url')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testDoesNotInvalidateOnNonPublishTransition(): void
    {
        $page = new Page('page-uuid-999');
        $page->setWebspaceKey('sulu_io');

        $event = new PageWorkflowTransitionAppliedEvent(
            $page,
            'request_for_review',
            'en'
        );

        $this->cacheManager->invalidateReference()->shouldNotBeCalled();
        $this->cacheManager->invalidatePath()->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateOnRemove(): void
    {
        $event = new PageRemovedEvent(
            'page-uuid-456',
            'sulu_io',
            'Test Page',
            ['locales' => ['en', 'de']]
        );

        $this->cacheManager->invalidateTag('page-uuid-456')
            ->shouldBeCalled();

        $this->subscriber->onPageRemoved($event);
    }

    public function testInvalidateOnUnpublish(): void
    {
        $page = new Page('page-uuid-789');
        $page->setWebspaceKey('sulu_io');

        $event = new PageWorkflowTransitionAppliedEvent(
            $page,
            WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => 'page-uuid-789',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->cacheManager->invalidateTag('page-uuid-789')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateExcerptTagsOnPublish(): void
    {
        $page = new Page('page-uuid-with-tags');
        $page->setWebspaceKey('sulu_io');

        $tag1 = (new Tag())->setName('Technology');
        $tag2 = (new Tag())->setName('CMS');

        $dimensionContent = new PageDimensionContent($page);
        $dimensionContent->setExcerptTags([$tag1, $tag2]);

        $event = new PageWorkflowTransitionAppliedEvent(
            $page,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => 'page-uuid-with-tags',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willReturn($dimensionContent);

        $this->cacheManager->invalidateTag('page-uuid-with-tags')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 'Technology')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 'CMS')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateExcerptCategoriesOnPublish(): void
    {
        $page = new Page('page-uuid-with-categories');
        $page->setWebspaceKey('sulu_io');

        $dimensionContent = new PageDimensionContent($page);
        $dimensionContent->setExcerptCategories([
            (new Category())->setId(10),
            (new Category())->setId(20),
        ]);

        $event = new PageWorkflowTransitionAppliedEvent(
            $page,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => PageInterface::RESOURCE_KEY,
            'resourceId' => 'page-uuid-with-categories',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($page, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willReturn($dimensionContent);

        $this->cacheManager->invalidateTag('page-uuid-with-categories')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', '10')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', '20')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }
}
