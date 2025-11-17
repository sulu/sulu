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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\HttpCache\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionAppliedEvent;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\HttpCache\EventSubscriber\ArticleCacheInvalidationSubscriber;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ArticleCacheInvalidationSubscriberTest extends TestCase
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

    private ArticleCacheInvalidationSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->cacheManager = $this->prophesize(CacheManagerInterface::class);
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->routeGenerator = $this->prophesize(RouteGeneratorInterface::class);

        $this->subscriber = new ArticleCacheInvalidationSubscriber(
            $this->cacheManager->reveal(),
            $this->routeRepository->reveal(),
            $this->contentAggregator->reveal(),
            $this->routeGenerator->reveal()
        );
    }

    public function testInvalidateTagOnPublish(): void
    {
        $article = new Article('article-uuid-123');

        $event = new ArticleWorkflowTransitionAppliedEvent(
            $article,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => 'article-uuid-123',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($article, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->cacheManager->invalidateTag('article-uuid-123')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidatePathsOnPublish(): void
    {
        $article = new Article('article-uuid-123');

        $event = new ArticleWorkflowTransitionAppliedEvent(
            $article,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $route1 = new Route(ArticleInterface::RESOURCE_KEY, 'article-uuid-123', 'en', '/en/blog/test-article');
        $route2 = new Route(ArticleInterface::RESOURCE_KEY, 'article-uuid-123', 'en', '/en/news/old-slug');

        $this->routeRepository->findBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => 'article-uuid-123',
            'locale' => 'en',
        ])->willReturn([$route1, $route2]);

        $this->contentAggregator->aggregate($article, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->routeGenerator->generate('/en/blog/test-article', 'en', null, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/en/blog/test-article');

        $this->routeGenerator->generate('/en/news/old-slug', 'en', null, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/en/news/old-slug');

        $this->cacheManager->invalidateTag('article-uuid-123')
            ->shouldBeCalled();
        $this->cacheManager->invalidatePath('https://example.com/en/blog/test-article')
            ->shouldBeCalled();
        $this->cacheManager->invalidatePath('https://example.com/en/news/old-slug')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testDoesNotInvalidateOnNonPublishTransition(): void
    {
        $article = new Article('article-uuid-999');

        $event = new ArticleWorkflowTransitionAppliedEvent(
            $article,
            'request_for_review',
            'en'
        );

        $this->cacheManager->invalidateReference()->shouldNotBeCalled();
        $this->cacheManager->invalidatePath()->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateOnRemove(): void
    {
        $event = new ArticleRemovedEvent(
            'article-uuid-456',
            'Test Article',
            ['locales' => ['en', 'de']]
        );

        $this->cacheManager->invalidateTag('article-uuid-456')
            ->shouldBeCalled();

        $this->subscriber->onArticleRemoved($event);
    }

    public function testInvalidateOnUnpublish(): void
    {
        $article = new Article('article-uuid-789');

        $event = new ArticleWorkflowTransitionAppliedEvent(
            $article,
            WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => 'article-uuid-789',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($article, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->cacheManager->invalidateTag('article-uuid-789')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateExcerptTagsOnPublish(): void
    {
        $article = new Article('article-uuid-with-tags');

        $tag1 = (new Tag())->setName('Technology');
        $tag2 = (new Tag())->setName('CMS');

        $dimensionContent = new ArticleDimensionContent($article);
        $dimensionContent->setExcerptTags([$tag1, $tag2]);

        $event = new ArticleWorkflowTransitionAppliedEvent(
            $article,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => 'article-uuid-with-tags',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($article, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willReturn($dimensionContent);

        $this->cacheManager->invalidateTag('article-uuid-with-tags')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 'Technology')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('tag', 'CMS')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateExcerptCategoriesOnPublish(): void
    {
        $article = new Article('article-uuid-with-categories');

        $dimensionContent = new ArticleDimensionContent($article);
        $dimensionContent->setExcerptCategories([
            (new Category())->setId(10),
            (new Category())->setId(20),
        ]);

        $event = new ArticleWorkflowTransitionAppliedEvent(
            $article,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $this->routeRepository->findBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => 'article-uuid-with-categories',
            'locale' => 'en',
        ])->willReturn([]);

        $this->contentAggregator->aggregate($article, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willReturn($dimensionContent);

        $this->cacheManager->invalidateTag('article-uuid-with-categories')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', '10')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('category', '20')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }
}
