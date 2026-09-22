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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentWorkflow\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Application\ContentWorkflow\Subscriber\PublishTransitionSubscriber;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Exception\ShadowSourceNotPublishedException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;

class PublishTransitionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function createContentPublisherSubscriberInstance(
        ContentCopierInterface $contentCopier,
        ?ContentAggregatorInterface $contentAggregator = null
    ): PublishTransitionSubscriber {
        return new PublishTransitionSubscriber(
            $contentCopier,
            $contentAggregator ?? $this->prophesize(ContentAggregatorInterface::class)->reveal()
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $this->assertSame([
            'workflow.content_workflow.transition.publish' => 'onPublish',
        ], $contentPublishSubscriber::getSubscribedEvents());
    }

    public function testOnPublishNoDimensionContentInterface(): void
    {
        $dimensionContent = $this->prophesize(WorkflowInterface::class);
        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::cetera())->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishNoDimensionContentCollection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No "dimensionContentCollection" given.');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishNoContentRichEntity(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No "contentRichEntity" given.');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishNoDimensionAttributes(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No "dimensionAttributes" given.');

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublish(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $versionDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            Argument::that(static fn (array $attrs) => isset($attrs['version']) && $attrs['version'] > 0),
            Argument::any()
        )
            ->willReturn($versionDimensionContent->reveal());

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishExistingPublished(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft', 'version' => DimensionContentInterface::CURRENT_VERSION];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getWorkflowPublished()->willReturn(new \DateTimeImmutable());
        $dimensionContent->setWorkflowPublished(Argument::any())->shouldNotBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $versionDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            Argument::that(static fn (array $attrs) => isset($attrs['version']) && $attrs['version'] > 0),
            Argument::any()
        )
            ->willReturn($versionDimensionContent->reveal());

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishShadow(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getShadowLocale()->willReturn('de');
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $sourceDimensionAttributes = $dimensionAttributes;
        $sourceDimensionAttributes['locale'] = 'de';
        $sourceDimensionAttributes['stage'] = 'live';
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $versionDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            Argument::that(static fn (array $attrs) => isset($attrs['version']) && $attrs['version'] > 0),
            Argument::any()
        )
            ->willReturn($versionDimensionContent->reveal());

        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $sourceDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $sourceDimensionContent->willImplement(TemplateInterface::class);
        $sourceDimensionContent->getTemplateKey()->willReturn('default');
        $contentAggregator->aggregate($contentRichEntity->reveal(), $sourceDimensionAttributes)
            ->willReturn($sourceDimensionContent->reveal())
            ->shouldBeCalled();

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContent(
            $sourceDimensionContent->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes,
            [
                'data' => [
                    'shadowOn' => true,
                    'shadowLocale' => 'de',
                ],
            ]
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance(
            $contentCopier->reveal(),
            $contentAggregator->reveal()
        );

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishShadowWithUnpublishedSourceThrows(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getShadowLocale()->willReturn('de');
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent($dimensionContent->reveal(), new Marking());
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::cetera())
            ->willReturn($this->prophesize(DimensionContentInterface::class)->reveal());
        // The shadow source must not be copied when it has no published template.
        $contentCopier->copyFromDimensionContent(Argument::cetera())->shouldNotBeCalled();

        $sourceDimensionAttributes = ['locale' => 'de', 'stage' => 'live'];

        // Source live content exists (e.g. an unlocalized live of the page) but carries no template.
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $sourceDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $sourceDimensionContent->willImplement(TemplateInterface::class);
        $sourceDimensionContent->getTemplateKey()->willReturn(null);
        $contentAggregator->aggregate($contentRichEntity->reveal(), $sourceDimensionAttributes)
            ->willReturn($sourceDimensionContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance(
            $contentCopier->reveal(),
            $contentAggregator->reveal()
        );

        $this->expectException(ShadowSourceNotPublishedException::class);

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishShadowWithMissingSourceThrows(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getShadowLocale()->willReturn('de');
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent($dimensionContent->reveal(), new Marking());
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $contentCopier->copyFromDimensionContentCollection(Argument::cetera())
            ->willReturn($this->prophesize(DimensionContentInterface::class)->reveal());
        $contentCopier->copyFromDimensionContent(Argument::cetera())->shouldNotBeCalled();

        $sourceDimensionAttributes = ['locale' => 'de', 'stage' => 'live'];

        // The source has no live content at all.
        $contentRichEntity->getId()->willReturn('some-uuid');
        $contentRichEntity->getDimensionContents()->willReturn(new ArrayCollection([]));
        $contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $contentAggregator->aggregate($contentRichEntity->reveal(), $sourceDimensionAttributes)
            ->willThrow(new ContentNotFoundException($contentRichEntity->reveal(), $sourceDimensionAttributes))
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance(
            $contentCopier->reveal(),
            $contentAggregator->reveal()
        );

        $this->expectException(ShadowSourceNotPublishedException::class);

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishHasShadow(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContent->willImplement(ShadowInterface::class);
        $dimensionContent->willImplement(TemplateInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getShadowLocale()->willReturn(null);
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $versionDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            Argument::that(static fn (array $attrs) => isset($attrs['version']) && $attrs['version'] > 0),
            Argument::any()
        )
            ->willReturn($versionDimensionContent->reveal());

        $resolvedCopiedContent = $this->prophesize(DimensionContentInterface::class);
        $resolvedCopiedContent->willImplement(ShadowInterface::class);
        $resolvedCopiedContent->getShadowLocalesForLocale('en')->willReturn(['de'])->shouldBeCalled();
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $targetDimensionAttributes['locale'] = 'de';
        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes,
            [
                'ignoredAttributes' => [
                    'shadowOn',
                    'shadowLocale',
                    'url',
                ],
            ]
        )
            ->willReturn($resolvedCopiedContent->reveal())
            ->shouldBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        $contentPublishSubscriber->onPublish($event);
    }

    public function testOnPublishFailedPublishDoesNotCreateVersion(): void
    {
        // The new version must only be created when the publish-side copy succeeds,
        // otherwise a failed publish (e.g. duplicate route) leaves an orphan version.
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(WorkflowInterface::class);
        $dimensionContentCollection = $this->prophesize(DimensionContentCollectionInterface::class);
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $dimensionAttributes = ['locale' => 'en', 'stage' => 'draft'];

        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->getWorkflowPublished()->willReturn(null);
        $dimensionContent->setWorkflowPublished(Argument::cetera())->shouldBeCalled();

        $event = new TransitionEvent(
            $dimensionContent->reveal(),
            new Marking()
        );
        $event->setContext([
            ContentWorkflowInterface::DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY => $dimensionContentCollection->reveal(),
            ContentWorkflowInterface::DIMENSION_ATTRIBUTES_CONTEXT_KEY => $dimensionAttributes,
            ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY => $contentRichEntity->reveal(),
        ]);

        $contentCopier = $this->prophesize(ContentCopierInterface::class);
        $targetDimensionAttributes = $dimensionAttributes;
        $targetDimensionAttributes['stage'] = 'live';

        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            $targetDimensionAttributes
        )
            ->willThrow(new \RuntimeException('Duplicate entry'));

        $contentCopier->copyFromDimensionContentCollection(
            $dimensionContentCollection->reveal(),
            $contentRichEntity->reveal(),
            Argument::that(static fn (array $attrs) => isset($attrs['version']) && $attrs['version'] > 0),
            Argument::any()
        )
            ->shouldNotBeCalled();

        $contentPublishSubscriber = $this->createContentPublisherSubscriberInstance($contentCopier->reveal());

        try {
            $contentPublishSubscriber->onPublish($event);
            $this->fail('Expected the publish failure to bubble up.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Duplicate entry', $e->getMessage());
        }
    }
}
