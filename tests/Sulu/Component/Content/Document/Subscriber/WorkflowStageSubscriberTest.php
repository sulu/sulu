<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Prophecy\Argument;
use Sulu\Component\Webspace\Webspace;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Document\Subscriber\WorkflowStageSubscriber;
use PHPCR\PropertyInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\DocumentAccessor;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

class WorkflowStageSubscriberTest extends SubscriberTestCase
{
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
     * It should set the published date when the stage changes to published
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
     * It should NOT set the published date when the stage has not changed
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
