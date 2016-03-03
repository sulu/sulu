<?php

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Subscriber\DocumentPublishSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\WorkflowStage;

class DocumentPublishSubscriberTest extends SubscriberTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->registry = $this->prophesize(DocumentManagerRegistry::class);
        $this->defaultManager = $this->prophesize(DocumentManagerInterface::class);
        $this->publishManager = $this->prophesize(DocumentManagerInterface::class);

        $this->subscriber = new DocumentPublishSubscriber(
            $this->registry->reveal(),
            'live'
        );
        $this->document = $this->prophesize('stdClass');
        $this->workflowDocument = $this->prophesize(WorkflowStageBehavior::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);

        $this->registry->getManager('live')->willReturn($this->publishManager->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->context->getInspector()->willReturn($this->inspector->reveal());
    }

    /**
     * It should do nothing if the default manager is not the default manager.
     */
    public function testDoNothingNotDefaultManager()
    {
        $this->context->getDocumentManager()->willReturn($this->publishManager->reveal());
        $this->publishManager->persist(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should do nothing if the document is not a workflow document.
     */
    public function testDoNothingNotWorkflowStage()
    {
        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->publishManager->persist(Argument::cetera())->shouldNotBeCalled();
        $this->publishManager->flush()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should sync a draft workflow document with a "new" PHPCR node.
     */
    public function testSyncNew()
    {
        $path = '/path/to/document';
        $locale = 'at';

        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getDocument()->willReturn($this->workflowDocument->reveal());
        $this->node->isNew()->willReturn(true);
        $this->workflowDocument->getWorkflowStage()->willReturn(WorkflowStage::TEST);

        $this->inspector->getLocale($this->workflowDocument->reveal())->willReturn($locale);
        $this->inspector->getPath($this->workflowDocument->reveal())->willReturn($path);

        $this->publishManager->persist(
            $this->workflowDocument->reveal(),
            $locale,
            [
                'path' => $path,
                'auto_create' => true,
            ]
        )->shouldBeCalled();
        $this->publishManager->flush()->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should not sync a draft workflow document that is not new.
     */
    public function testSyncOldNotPublished()
    {
        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getDocument()->willReturn($this->workflowDocument->reveal());
        $this->node->isNew()->willReturn(false);
        $this->workflowDocument->getWorkflowStage()->willReturn(WorkflowStage::TEST);

        $this->publishManager->persist(Argument::cetera())->shouldNotBeCalled();
        $this->publishManager->flush()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should sync a publishable, published document.
     */
    public function testSyncPublishablePublished()
    {
        $path = '/path/to/document';
        $locale = 'at';

        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getDocument()->willReturn($this->workflowDocument->reveal());
        $this->node->isNew()->willReturn(false);
        $this->workflowDocument->getWorkflowStage()->willReturn(WorkflowStage::PUBLISHED);

        $this->inspector->getLocale($this->workflowDocument->reveal())->willReturn($locale);
        $this->inspector->getPath($this->workflowDocument->reveal())->willReturn($path);

        $this->publishManager->persist(
            $this->workflowDocument->reveal(),
            $locale,
            [
                'path' => $path,
                'auto_create' => true,
            ]
        )->shouldBeCalled();

        $this->publishManager->flush()->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

}
