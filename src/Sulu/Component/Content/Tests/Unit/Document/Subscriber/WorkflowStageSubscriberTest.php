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
use PHPCR\SessionInterface;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Subscriber\WorkflowStageSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\PropertyEncoder;

class WorkflowStageSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var WorkflowStageSubscriber
     */
    private $workflowStageSubscriber;

    /**
     * @var WorkflowStageBehavior
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $defaultNode;

    /**
     * @var NodeInterface
     */
    private $liveNode;

    /**
     * @var DocumentAccessor
     */
    private $documentAccessor;

    public function setUp()
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->defaultSession = $this->prophesize(SessionInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);

        $this->workflowStageSubscriber = new WorkflowStageSubscriber(
            $this->propertyEncoder->reveal(),
            $this->documentInspector->reveal(),
            $this->defaultSession->reveal(),
            $this->liveSession->reveal()
        );

        $this->document = $this->prophesize(WorkflowStageBehavior::class);
        $this->defaultNode = $this->prophesize(NodeInterface::class);
        $this->liveNode = $this->prophesize(NodeInterface::class);
        $this->documentAccessor = $this->prophesize(DocumentAccessor::class);

        $this->propertyEncoder->localizedSystemName('state', 'de')->willReturn('i18n:de-state');
        $this->propertyEncoder->localizedSystemName('published', 'de')->willReturn('i18n:de-published');

        $this->documentInspector->getPath($this->document->reveal())->willReturn('/some/path');

        $this->defaultSession->getNode('/some/path')->willReturn($this->defaultNode->reveal());
        $this->liveSession->getNode('/some/path')->willReturn($this->liveNode->reveal());
    }

    public function testSetWorkflowStageOnDocument()
    {
        $publishedDate = new \DateTime();
        $event = $this->getHydrateEventMock();
        $this->defaultNode
            ->getPropertyValueWithDefault('i18n:de-state', WorkflowStage::TEST)
            ->willReturn(WorkflowStage::PUBLISHED);
        $this->defaultNode->getPropertyValueWithDefault('i18n:de-published', null)->willReturn($publishedDate);

        $this->document->setWorkflowStage(WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->documentAccessor->set('published', $publishedDate)->shouldBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageOnDocument($event->reveal());
    }

    public function testSetWorkflowStageOnDocumentWithWrongDocument()
    {
        $event = $this->getHydrateEventMock();
        $event->getDocument()->willReturn(new \stdClass());

        $this->documentAccessor->set('published', Argument::any())->shouldNotBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageOnDocument($event->reveal());
    }

    public function testSetWorkflowStageOnDocumentWithoutLocale()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $event->getLocale()->willReturn(null);

        $document = $this->prophesize(WorkflowStageBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->documentAccessor->set('published', Argument::any())->shouldNotBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageOnDocument($event->reveal());
    }

    public function testSetWorkflowStageToTest()
    {
        $event = $this->getPersistEventMock();

        $this->document->getPublished()->willReturn(new \DateTime());

        $this->document->setWorkflowStage(WorkflowStage::TEST)->shouldBeCalled();
        $this->defaultNode->setProperty('i18n:de-state', WorkflowStage::TEST)->shouldBeCalled();
        $this->defaultNode->setProperty('i18n:de-published', Argument::any())->shouldNotBeCalled();
        $this->liveNode->setProperty('i18n:de-state', Argument::any())->shouldNotBeCalled();
        $this->liveNode->setProperty('i18n:de-published', Argument::any())->shouldNotBeCalled();

        $this->documentAccessor->set('published', Argument::any())->shouldNotBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageToTest($event->reveal());
    }

    public function testSetWorkflowStageToTestWithWrongDocument()
    {
        $event = $this->getPersistEventMock();
        $event->getDocument()->willReturn(new \stdClass());

        $this->workflowStageSubscriber->setWorkflowStageToTest($event->reveal());
    }

    public function testSetWorkflowStageToTestWithoutLocale()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getLocale()->willReturn(null);

        $document = $this->prophesize(WorkflowStageBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->documentAccessor->set('published', Argument::any())->shouldNotBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageToTest($event->reveal());
    }

    public function testSetWorkflowStageToPublished()
    {
        $event = $this->getPublishEventMock();

        $this->document->getPublished()->willReturn(new \DateTime());

        $this->document->setWorkflowStage(WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->defaultNode->setProperty('i18n:de-state', WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->defaultNode->setProperty('i18n:de-published', Argument::any())->shouldNotBeCalled();
        $this->liveNode->setProperty('i18n:de-state', WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->liveNode->setProperty('i18n:de-published', Argument::any())->shouldNotBeCalled();

        $this->documentAccessor->set('published', Argument::any())->shouldNotBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageToPublished($event->reveal());
    }

    public function testSetWorkflowStageToPublishedWithDraft()
    {
        $event = $this->getPublishEventMock();

        $this->document->getPublished()->willReturn(null);

        $this->document->setWorkflowStage(WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->defaultNode->setProperty('i18n:de-state', WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->defaultNode->setProperty('i18n:de-published', Argument::type(\DateTime::class))->shouldBeCalled();
        $this->liveNode->setProperty('i18n:de-state', WorkflowStage::PUBLISHED)->shouldBeCalled();
        $this->liveNode->setProperty('i18n:de-published', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->documentAccessor->set('published', Argument::type(\DateTime::class))->shouldBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageToPublished($event->reveal());
    }

    public function testSetWorkflowStageToPublishedWithWrongDocument()
    {
        $event = $this->getPublishEventMock();
        $event->getDocument()->willReturn(new \stdClass());

        $this->workflowStageSubscriber->setWorkflowStageToPublished($event->reveal());
    }

    public function testSetWorkflowStageToPublishedWithoutLocale()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getLocale()->willReturn(null);

        $document = $this->prophesize(WorkflowStageBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->documentAccessor->set('published', Argument::any())->shouldNotBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageToPublished($event->reveal());
    }

    public function testSetWorkflowStageToTestAndResetPublishedDate()
    {
        $document = $this->prophesize(WorkflowStageBehavior::class);
        $this->documentInspector->getPath($document->reveal())->willReturn('/cmf/sulu_io/contents');

        $event = $this->prophesize(UnpublishEvent::class);
        $event->getLocale()->willReturn('de');
        $event->getDocument()->willReturn($document->reveal());

        $node = $this->prophesize(NodeInterface::class);
        $node->setProperty('i18n:de-state', WorkflowStage::TEST)->shouldBeCalled();
        $node->setProperty('i18n:de-published', null)->shouldBeCalled();

        $this->defaultSession->getNode('/cmf/sulu_io/contents')->willReturn($node->reveal());

        $this->workflowStageSubscriber->setWorkflowStageToTestAndResetPublishedDate($event->reveal());
    }

    public function testSetWorkflowStageToTestAndResetPublishedDateWithoutLocale()
    {
        $event = $this->prophesize(UnpublishEvent::class);
        $event->getLocale()->willReturn(null);

        $document = $this->prophesize(WorkflowStageBehavior::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->defaultSession->getNode(Argument::cetera())->shouldNotBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageToTestAndResetPublishedDate($event->reveal());
    }

    public function testSetWorkflowStageToTestForCopy()
    {
        $event = $this->prophesize(CopyEvent::class);
        $event->getCopiedNode()->willReturn($this->defaultNode->reveal());
        $this->propertyEncoder->localizedSystemName('state', '*')->willReturn('i18n:*-state');
        $this->propertyEncoder->localizedSystemName('published', '*')->willReturn('i18n:*-published');

        $defaultGermanStateProperty = $this->prophesize(PropertyInterface::class);
        $defaultEnglishStateProperty = $this->prophesize(PropertyInterface::class);
        $this->defaultNode->getProperties('i18n:*-state')->willReturn([
            $defaultGermanStateProperty->reveal(),
            $defaultEnglishStateProperty->reveal(),
        ]);

        $defaultGermanPublishedProperty = $this->prophesize(PropertyInterface::class);
        $defaultEnglishPublishedProperty = $this->prophesize(PropertyInterface::class);
        $this->defaultNode->getProperties('i18n:*-published')->willReturn([
            $defaultGermanPublishedProperty->reveal(),
            $defaultEnglishPublishedProperty->reveal(),
        ]);

        $childNode = $this->prophesize(NodeInterface::class);
        $childNode->getNodes()->willReturn([]);
        $this->defaultNode->getNodes()->willReturn([$childNode->reveal()]);

        $defaultGermanChildStateProperty = $this->prophesize(PropertyInterface::class);
        $defaultEnglishChildStateProperty = $this->prophesize(PropertyInterface::class);
        $childNode->getProperties('i18n:*-state')->willReturn([
            $defaultGermanChildStateProperty->reveal(),
            $defaultEnglishChildStateProperty->reveal(),
        ]);

        $defaultGermanChildPublishedProperty = $this->prophesize(PropertyInterface::class);
        $defaultEnglishChildPublishedProperty = $this->prophesize(PropertyInterface::class);
        $childNode->getProperties('i18n:*-published')->willReturn([
            $defaultGermanChildPublishedProperty->reveal(),
            $defaultEnglishChildPublishedProperty->reveal(),
        ]);

        $defaultGermanStateProperty->setValue(WorkflowStage::TEST)->shouldBeCalled();
        $defaultEnglishStateProperty->setValue(WorkflowStage::TEST)->shouldBeCalled();
        $defaultGermanPublishedProperty->setValue(null)->shouldBeCalled();
        $defaultEnglishPublishedProperty->setValue(null)->shouldBeCalled();
        $defaultGermanChildStateProperty->setValue(WorkflowStage::TEST)->shouldBeCalled();
        $defaultEnglishChildStateProperty->setValue(WorkflowStage::TEST)->shouldBeCalled();
        $defaultGermanChildPublishedProperty->setValue(null)->shouldBeCalled();
        $defaultEnglishChildPublishedProperty->setValue(null)->shouldBeCalled();

        $this->workflowStageSubscriber->setWorkflowStageToTestForCopy($event->reveal());
    }

    /**
     * @return HydrateEvent
     */
    private function getHydrateEventMock()
    {
        $event = $this->prophesize(HydrateEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getNode()->willReturn($this->defaultNode->reveal());
        $event->getLocale()->willReturn('de');
        $event->getAccessor()->willReturn($this->documentAccessor->reveal());

        return $event;
    }

    /**
     * @return PersistEvent
     */
    private function getPersistEventMock()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getAccessor()->willReturn($this->documentAccessor->reveal());

        return $event;
    }

    /**
     * @return PublishEvent
     */
    private function getPublishEventMock()
    {
        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($this->document->reveal());
        $event->getLocale()->willReturn('de');
        $event->getAccessor()->willReturn($this->documentAccessor->reveal());

        return $event;
    }
}
