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

namespace Sulu\Snippet\Tests\Unit\Infrastructure\Sulu\HttpCache\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Snippet\Domain\Event\SnippetRemovedEvent;
use Sulu\Snippet\Domain\Event\SnippetWorkflowTransitionAppliedEvent;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Infrastructure\Sulu\HttpCache\EventSubscriber\SnippetCacheInvalidationSubscriber;

class SnippetCacheInvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CacheManagerInterface>
     */
    private ObjectProphecy $cacheManager;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private ObjectProphecy $contentAggregator;

    private SnippetCacheInvalidationSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->cacheManager = $this->prophesize(CacheManagerInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);

        $this->subscriber = new SnippetCacheInvalidationSubscriber(
            $this->cacheManager->reveal(),
            $this->contentAggregator->reveal(),
            []
        );
    }

    public function testInvalidateTagOnPublish(): void
    {
        $snippet = new Snippet('snippet-uuid-123');

        $event = new SnippetWorkflowTransitionAppliedEvent(
            $snippet,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $this->contentAggregator->aggregate($snippet, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->cacheManager->invalidateTag('snippet-uuid-123')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateTagOnUnpublish(): void
    {
        $snippet = new Snippet('snippet-uuid-789');

        $event = new SnippetWorkflowTransitionAppliedEvent(
            $snippet,
            WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH,
            'en'
        );

        $this->contentAggregator->aggregate($snippet, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willThrow(ContentNotFoundException::class);

        $this->cacheManager->invalidateTag('snippet-uuid-789')
            ->shouldBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testDoesNotInvalidateOnNonPublishTransition(): void
    {
        $snippet = new Snippet('snippet-uuid-999');

        $event = new SnippetWorkflowTransitionAppliedEvent(
            $snippet,
            'request_for_review',
            'en'
        );

        $this->cacheManager->invalidateReference()->shouldNotBeCalled();
        $this->cacheManager->invalidateTag()->shouldNotBeCalled();

        $this->subscriber->onWorkflowTransition($event);
    }

    public function testInvalidateOnRemove(): void
    {
        $event = new SnippetRemovedEvent(
            'snippet-uuid-456',
            'Test Snippet',
            ['locales' => ['en', 'de']]
        );

        $this->cacheManager->invalidateTag('snippet-uuid-456')
            ->shouldBeCalled();

        $this->subscriber->onSnippetRemoved($event);
    }

    public function testInvalidateSnippetAreaOnPublish(): void
    {
        $snippet = new Snippet('snippet-uuid-with-area');
        $dimensionContent = new \Sulu\Snippet\Domain\Model\SnippetDimensionContent($snippet);
        $dimensionContent->setTemplateKey('footer');

        $event = new SnippetWorkflowTransitionAppliedEvent(
            $snippet,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $areas = [
            'footer' => [
                'areaKey' => 'footer',
                'template' => 'footer',
                'cache-invalidation' => true,
            ],
        ];

        $subscriber = new SnippetCacheInvalidationSubscriber(
            $this->cacheManager->reveal(),
            $this->contentAggregator->reveal(),
            $areas
        );

        $this->contentAggregator->aggregate($snippet, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willReturn($dimensionContent);

        $this->cacheManager->invalidateTag('snippet-uuid-with-area')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('snippet_area', 'footer')
            ->shouldBeCalled();

        $subscriber->onWorkflowTransition($event);
    }

    public function testDoesNotInvalidateSnippetAreaWhenCacheInvalidationDisabled(): void
    {
        $snippet = new Snippet('snippet-uuid-no-cache');
        $dimensionContent = new \Sulu\Snippet\Domain\Model\SnippetDimensionContent($snippet);
        $dimensionContent->setTemplateKey('header');

        $event = new SnippetWorkflowTransitionAppliedEvent(
            $snippet,
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            'en'
        );

        $areas = [
            'header' => [
                'areaKey' => 'header',
                'template' => 'header',
                'cache-invalidation' => false,
            ],
        ];

        $subscriber = new SnippetCacheInvalidationSubscriber(
            $this->cacheManager->reveal(),
            $this->contentAggregator->reveal(),
            $areas
        );

        $this->contentAggregator->aggregate($snippet, [
            'locale' => 'en',
            'stage' => 'live',
        ])->willReturn($dimensionContent);

        $this->cacheManager->invalidateTag('snippet-uuid-no-cache')
            ->shouldBeCalled();
        $this->cacheManager->invalidateReference('snippet_area', 'header')
            ->shouldNotBeCalled();

        $subscriber->onWorkflowTransition($event);
    }
}
