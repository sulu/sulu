<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventSubscriber;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveDraftEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;

class StructureSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var StructureSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);
        $this->subscriber = new StructureSubscriber($this->searchManager->reveal());
    }

    public function testIndexPersistedDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $persistEvent = $this->getPersistEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPersistedDocument($persistEvent->reveal());
    }

    public function testIndexPersistedDocumentUnsecuredDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn([]);

        $persistEvent = $this->getPersistEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPersistedDocument($persistEvent->reveal());
    }

    public function testIndexPersistedDocumentSecuredDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn(['some' => 'permissions']);

        $persistEvent = $this->getPersistEventMock($document->reveal());

        $this->searchManager->index($document)->shouldNotBeCalled();

        $this->subscriber->indexPersistedDocument($persistEvent->reveal());
    }

    public function testIndexPublishedDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $publishEvent = $this->getPublishEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPublishedDocument($publishEvent->reveal());
    }

    public function testIndexPublishedDocumentUnsecuredDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn([]);

        $publishEvent = $this->getPublishEventMock($document->reveal());

        $this->searchManager->index($document)->shouldBeCalled();

        $this->subscriber->indexPublishedDocument($publishEvent->reveal());
    }

    public function testIndexPublishedDocumentSecuredDocument()
    {
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(SecurityBehavior::class);
        $document->getPermissions()->willReturn(['some' => 'permissions']);

        $publishEvent = $this->getPublishEventMock($document->reveal());

        $this->searchManager->index($document)->shouldNotBeCalled();

        $this->subscriber->indexPublishedDocument($publishEvent->reveal());
    }

    public function testIndexDocumentAfterRemoveDraft()
    {
        $removeDraftEvent = $this->prophesize(RemoveDraftEvent::class);
        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(WorkflowStageBehavior::class);
        $removeDraftEvent->getDocument()->willReturn($document);

        $document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();
        $this->searchManager->index($document)->shouldBeCalled();
        $document->setWorkflowStage(WorkflowStage::PUBLISHED)->shouldBeCalled();

        $this->subscriber->indexDocumentAfterRemoveDraft($removeDraftEvent->reveal());
    }

    public function testDeindexRemovedDocument()
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);

        $document = $this->prophesize(StructureBehavior::class);
        $removeEvent->getDocument()->willReturn($document);

        $this->searchManager->deindex($document)->shouldBeCalled();

        $this->subscriber->deindexRemovedDocument($removeEvent->reveal());
    }

    public function testDeindexRemovedDocumentWithWorkflowStageBehavior()
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);

        $document = $this->prophesize(StructureBehavior::class)
            ->willImplement(WorkflowStageBehavior::class);
        $removeEvent->getDocument()->willReturn($document);

        $document->getWorkflowStage()->willReturn(WorkflowStage::TEST);

        $document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();
        $this->searchManager->deindex($document)->shouldBeCalled();

        $document->setWorkflowStage(WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->searchManager->deindex($document)->shouldBeCalled();

        $document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();

        $this->subscriber->deindexRemovedDocument($removeEvent->reveal());
    }

    public function testDeindexUnpublishedDocument()
    {
        $unpublishEvent = $this->prophesize(UnpublishEvent::class);

        $document = $this->prophesize(StructureBehavior::class);
        $unpublishEvent->getDocument()->willReturn($document->reveal());

        $this->searchManager->deindex($document)->shouldBeCalled();

        $this->subscriber->deindexUnpublishedDocument($unpublishEvent->reveal());
    }

    private function getPersistEventMock($document)
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $persistEvent->getDocument()->willReturn($document);

        return $persistEvent;
    }

    private function getPublishEventMock($document)
    {
        $publishEvent = $this->prophesize(PublishEvent::class);
        $publishEvent->getDocument()->willReturn($document);

        return $publishEvent;
    }
}
