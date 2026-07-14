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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentWorkflow;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflow;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Exception\PublishWithoutRouteException;
use Sulu\Content\Domain\Exception\UnavailableContentTransitionException;
use Sulu\Content\Domain\Exception\UnknownContentTransitionException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Unit\Mocks\DimensionContentMockWrapperTrait;
use Sulu\Content\Tests\Unit\Mocks\MockWrapper;
use Sulu\Content\Tests\Unit\Mocks\WorkflowMockWrapperTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class ContentWorkflowTest extends TestCase
{
    use ProphecyTrait;

    protected function createContentWorkflowInstance(
        ContentMetadataInspectorInterface $contentMetadataInspector,
        ContentMergerInterface $contentMerger,
        ?EventDispatcherInterface $eventDispatcher = null
    ): ContentWorkflowInterface {
        return new ContentWorkflow(
            $contentMetadataInspector,
            $contentMerger,
            null,
            $eventDispatcher
        );
    }

    /**
     * @template T of DimensionContentInterface&WorkflowInterface
     *
     * @param ObjectProphecy<T> $templateMock
     *
     * @return T
     */
    protected function wrapWorkflowMock(ObjectProphecy $templateMock): WorkflowInterface
    {
        return new class($templateMock) extends MockWrapper implements // @phpstan-ignore-line
            DimensionContentInterface,
            WorkflowInterface {
            use DimensionContentMockWrapperTrait;
            use WorkflowMockWrapperTrait;

            /**
             * @var T
             */
            protected $instance;

            public static function getWorkflowName(): string
            {
                return 'content_workflow';
            }
        };
    }

    public function testTransitionNoWorkflowInterface(): void
    {
        $this->expectException(\RuntimeException::class);

        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $contentMetadataInspector->reveal(),
            $contentMerger->reveal()
        );

        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getLocale()->willReturn('de');
        $dimensionContent1->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $this->expectExceptionMessage(\sprintf(
            'Expected "%s" but "%s" given.',
            WorkflowInterface::class,
            \get_class($dimensionContent2->reveal()))
        );

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
            $dimensionContent2->reveal(),
        ]);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntity->getDimensionContents()->willReturn($dimensionContents);

        $contentMetadataInspector->getDimensionContentClass($contentRichEntity->reveal()::class)
            ->willReturn(ExampleDimensionContent::class);

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            'request_for_review'
        );
    }

    public function testTransitionNoLocalizedDimensionContent(): void
    {
        $this->expectException(ContentNotFoundException::class);

        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $contentMetadataInspector->reveal(),
            $contentMerger->reveal()
        );

        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');

        $dimensionContents = new ArrayCollection([
            $dimensionContent1->reveal(),
        ]);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntity->getDimensionContents()->willReturn($dimensionContents);
        $contentRichEntity->getId()->willReturn('test-id');

        $contentMetadataInspector->getDimensionContentClass($contentRichEntity->reveal()::class)
            ->willReturn(ExampleDimensionContent::class);

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            'request_for_review'
        );
    }

    public function testNotExistTransition(): void
    {
        $this->expectException(UnknownContentTransitionException::class);
        $this->expectExceptionMessage(
            'Transition "not-exist-transition" is not defined for workflow "content_workflow".'
        );

        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $contentMetadataInspector->reveal(),
            $contentMerger->reveal()
        );

        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->willImplement(WorkflowInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent2->willImplement(WorkflowInterface::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getStage()->willReturn('draft');
        $dimensionContent2->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2->getWorkflowPlace()
            ->willReturn('unpublished')
            ->shouldBeCalled();

        $dimensionContents = new ArrayCollection([
            $this->wrapWorkflowMock($dimensionContent1),
            $this->wrapWorkflowMock($dimensionContent2),
        ]);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntity->getDimensionContents()->willReturn($dimensionContents);

        $contentMetadataInspector->getDimensionContentClass($contentRichEntity->reveal()::class)
            ->willReturn(ExampleDimensionContent::class);

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            'not-exist-transition'
        );
    }

    #[DataProvider('transitionProvider')]
    public function testTransitions(
        string $currentPlace,
        string $transitionName,
        bool $isTransitionAllowed
    ): void {
        if (!$isTransitionAllowed) {
            $this->expectException(UnavailableContentTransitionException::class);
        }

        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $contentWorkflow = $this->createContentWorkflowInstance(
            $contentMetadataInspector->reveal(),
            $contentMerger->reveal()
        );

        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->willImplement(WorkflowInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent2->willImplement(WorkflowInterface::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getStage()->willReturn('draft');
        $dimensionContent2->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2->getWorkflowPlace()
            ->willReturn($currentPlace)
            ->shouldBeCalled();

        if ($isTransitionAllowed) {
            $dimensionContent2->setWorkflowPlace(Argument::any(), Argument::any())
                ->shouldBeCalled();
        }

        $dimensionContents = new ArrayCollection([
            $this->wrapWorkflowMock($dimensionContent1),
            $this->wrapWorkflowMock($dimensionContent2),
        ]);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntity->getDimensionContents()->willReturn($dimensionContents);

        $contentMetadataInspector->getDimensionContentClass($contentRichEntity->reveal()::class)
            ->willReturn(ExampleDimensionContent::class);

        $dimensionContentCollection = new DimensionContentCollection($dimensionContents, $dimensionAttributes, ExampleDimensionContent::class);

        $mergedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $contentMerger->merge(Argument::type(DimensionContentCollection::class))
            ->willReturn($mergedDimensionContent)
            ->shouldBeCalledTimes($isTransitionAllowed ? 1 : 0);

        $this->assertSame(
            $isTransitionAllowed ? $mergedDimensionContent->reveal() : null,
            $contentWorkflow->apply(
                $contentRichEntity->reveal(),
                $dimensionAttributes,
                $transitionName
            )
        );
    }

    public function testTransitionBlockedByPublishWithoutRouteGuard(): void
    {
        $this->expectException(PublishWithoutRouteException::class);

        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            'workflow.content_workflow.guard.publish',
            static function(GuardEvent $event): void {
                $event->addTransitionBlocker(new TransitionBlocker('blocked', PublishWithoutRouteException::TRANSITION_BLOCKER_CODE));
            }
        );

        $contentWorkflow = $this->createContentWorkflowInstance(
            $contentMetadataInspector->reveal(),
            $contentMerger->reveal(),
            $eventDispatcher
        );

        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->willImplement(WorkflowInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent2->willImplement(WorkflowInterface::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getStage()->willReturn('draft');
        $dimensionContent2->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2->getWorkflowPlace()
            ->willReturn('unpublished')
            ->shouldBeCalled();

        $dimensionContents = new ArrayCollection([
            $this->wrapWorkflowMock($dimensionContent1),
            $this->wrapWorkflowMock($dimensionContent2),
        ]);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntity->getDimensionContents()->willReturn($dimensionContents);

        $contentMetadataInspector->getDimensionContentClass($contentRichEntity->reveal()::class)
            ->willReturn(ExampleDimensionContent::class);

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            'publish'
        );
    }

    public function testTransitionBlockedByOtherGuardStaysUnavailable(): void
    {
        $this->expectException(UnavailableContentTransitionException::class);

        $contentMetadataInspector = $this->prophesize(ContentMetadataInspectorInterface::class);
        $contentMerger = $this->prophesize(ContentMergerInterface::class);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            'workflow.content_workflow.guard.publish',
            static function(GuardEvent $event): void {
                $event->addTransitionBlocker(new TransitionBlocker('blocked', 'something_else'));
            }
        );

        $contentWorkflow = $this->createContentWorkflowInstance(
            $contentMetadataInspector->reveal(),
            $contentMerger->reveal(),
            $eventDispatcher
        );

        $dimensionAttributes = ['locale' => 'de', 'stage' => 'draft'];

        $dimensionContent1 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent1->willImplement(WorkflowInterface::class);
        $dimensionContent1->getLocale()->willReturn(null);
        $dimensionContent1->getStage()->willReturn('draft');
        $dimensionContent1->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2 = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent2->willImplement(WorkflowInterface::class);
        $dimensionContent2->getLocale()->willReturn('de');
        $dimensionContent2->getStage()->willReturn('draft');
        $dimensionContent2->getVersion()->willReturn(DimensionContentInterface::CURRENT_VERSION);

        $dimensionContent2->getWorkflowPlace()
            ->willReturn('unpublished')
            ->shouldBeCalled();

        $dimensionContents = new ArrayCollection([
            $this->wrapWorkflowMock($dimensionContent1),
            $this->wrapWorkflowMock($dimensionContent2),
        ]);

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntity->getDimensionContents()->willReturn($dimensionContents);

        $contentMetadataInspector->getDimensionContentClass($contentRichEntity->reveal()::class)
            ->willReturn(ExampleDimensionContent::class);

        $contentWorkflow->apply(
            $contentRichEntity->reveal(),
            $dimensionAttributes,
            'publish'
        );
    }

    /**
     * @return \Generator<mixed[]>
     */
    public static function transitionProvider(): \Generator
    {
        $places = [
            'unpublished' => [
                'request_for_review' => true,
                'reject' => false,
                'publish' => true,
                'unpublish' => false,
                'edit' => true,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => false,
            ],
            'review' => [
                'request_for_review' => false,
                'reject' => true,
                'publish' => true,
                'unpublish' => false,
                'edit' => false,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => false,
            ],
            'published' => [
                'request_for_review' => false,
                'reject' => false,
                'publish' => true,
                'unpublish' => true,
                'edit' => true,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => false,
            ],
            'draft' => [
                'request_for_review' => false,
                'reject' => false,
                'publish' => true,
                'unpublish' => true,
                'edit' => true,
                'remove_draft' => true,
                'request_for_review_draft' => true,
                'reject_draft' => false,
            ],
            'review_draft' => [
                'request_for_review' => false,
                'reject' => false,
                'publish' => true,
                'unpublish' => false,
                'edit' => false,
                'remove_draft' => false,
                'request_for_review_draft' => false,
                'reject_draft' => true,
            ],
        ];

        foreach ($places as $place => $transitions) {
            foreach ($transitions as $transition => $allowed) {
                yield [
                    $place,
                    $transition,
                    $allowed,
                ];
            }
        }
    }
}
