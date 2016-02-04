<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Subscriber\WorkflowStageSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class WorkflowStageSubscriberTest extends SubscriberTestCase
{
    /**
     * @var PropertyInterface
     */
    private $property;

    /**
     * @var WorkflowStageSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->accessor = $this->prophesize(DocumentAccessor::class);
        $this->property = $this->prophesize(PropertyInterface::class);

        $this->subscriber = new WorkflowStageSubscriber($this->encoder->reveal());

        $this->persistEvent->getNode()->willReturn($this->node);
        $this->persistEvent->getAccessor()->willReturn($this->accessor->reveal());
    }

    /**
     * It should return early if the locale is null.
     */
    public function testPersistLocaleIsNull()
    {
        $document = new TestWorkflowStageDocument(WorkflowStage::PUBLISHED);

        $this->persistEvent->getDocument()->willReturn($document);
        $this->persistEvent->getLocale()->willReturn(null);
        $this->node->setProperty()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should set the published date when the stage changes to published.
     */
    public function testPublishedTransition()
    {
        $document = new TestWorkflowStageDocument(WorkflowStage::PUBLISHED);
        $this->node->getPropertyValueWithDefault('stage', null)->willReturn(WorkflowStage::TEST);

        $this->persistEvent->getDocument()->willReturn($document);
        $this->persistEvent->getLocale()->willReturn('fr');

        $this->encoder->localizedSystemName(WorkflowStageSubscriber::WORKFLOW_STAGE_FIELD, 'fr')->willReturn('stage');

        $this->assertEquals(WorkflowStage::PUBLISHED, $document->getWorkflowStage());
        $this->accessor->set('published', Argument::type('DateTime'))->shouldBeCalled();
        $this->subscriber->handlePersist(
            $this->persistEvent->reveal()
        );
    }

    /**
     * It should NOT set the published date when the stage has not changed.
     */
    public function testPublishedNoTransition()
    {
        $document = new TestWorkflowStageDocument(WorkflowStage::PUBLISHED);
        $this->node->getPropertyValueWithDefault('stage', null)->willReturn(WorkflowStage::PUBLISHED);

        $this->persistEvent->getDocument()->willReturn($document);
        $this->persistEvent->getLocale()->willReturn('fr');
        $this->encoder->localizedSystemName(WorkflowStageSubscriber::WORKFLOW_STAGE_FIELD, 'fr')->willReturn('stage');

        $this->accessor->set('published', Argument::type('DateTime'))->shouldNotBeCalled();
        $this->assertEquals(WorkflowStage::PUBLISHED, $document->getWorkflowStage());
        $this->subscriber->handlePersist(
            $this->persistEvent->reveal()
        );
    }
}

class TestWorkflowStageDocument implements WorkflowStageBehavior
{
    private $workflowStage;
    private $published;

    public function __construct($stage)
    {
        $this->workflowStage = $stage;
    }

    public function getWorkflowStage()
    {
        return $this->workflowStage;
    }

    public function setWorkflowStage($workflowStage)
    {
        $this->workflowStage = $workflowStage;
    }

    public function getPublished()
    {
        return $this->published;
    }
}
