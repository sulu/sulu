<?php

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Subscriber\DocumentSynchronizationSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Document\SynchronizationManager;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\DocumentManager\Event\RemoveEvent;

class DocumentSynchronizationSubscriberTest extends SubscriberTestCase
{
    /**
     * @var mixed
     */
    private $defaultManager;

    /**
     * @var mixed
     */
    private $syncManager;

    /**
     * @var mixed
     */
    private $publishManager;

    /**
     * @var mixed
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();
        $this->defaultManager = $this->prophesize(DocumentManagerInterface::class);
        $this->syncManager = $this->prophesize(SynchronizationManager::class);
        $this->publishManager = $this->prophesize(DocumentManagerInterface::class);

        $this->subscriber = new DocumentSynchronizationSubscriber(
            $this->defaultManager->reveal(),
            $this->syncManager->reveal()
        );
        $this->document = $this->prophesize(SynchronizeBehavior::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->syncManager->getPublishDocumentManager()->willReturn($this->publishManager->reveal());
        $this->removeEvent = $this->prophesize(RemoveEvent::class);
        $this->removeEvent->getDocument()->willReturn($this->document->reveal());
        $this->removeEvent->getContext()->willReturn($this->context->reveal());
    }

    /**
     * It should do nothing if the default manager is not the default manager.
     */
    public function testDoNothingNotDefaultManager()
    {
        $this->context->getDocumentManager()->willReturn($this->publishManager->reveal());
        $this->publishManager->flush()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should do nothing if the document is not a synchronize document.
     */
    public function testDoNothingNotWorkflowStage()
    {
        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->persistEvent->getDocument()->willReturn(new \stdClass());
        $this->publishManager->flush()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should synchronize a document with a new PHPCR node.
     */
    public function testSyncNew()
    {
        $locale = 'at';

        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->node->isNew()->willReturn(true);

        $this->defaultManager->getInspector()->willReturn($this->inspector->reveal());
        $this->inspector->getLocale($this->document->reveal())->willReturn($locale);
        $this->inspector->getUuid($this->document->reveal())->willReturn('1234');
        $this->defaultManager->find('1234', $locale)->shouldBeCalled();

        $this->defaultManager->flush()->shouldBeCalled();
        $this->publishManager->flush()->shouldBeCalled();
        $this->syncManager->synchronizeSingle($this->document->reveal())->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should NOT persist a document with a not-new PHPCR node.
     */
    public function testSyncOld()
    {
        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->node->isNew()->willReturn(false);

        $this->defaultManager->flush()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());
    }

    /**
     * It should remove documents from the PDM that are removed in the PDM.
     */
    public function testRemove()
    {
        $this->context->getDocumentManager()->willReturn($this->defaultManager->reveal());
        $this->publishManager->remove($this->document->reveal())->shouldBeCalled();
        $this->defaultManager->flush()->shouldNotBeCalled();
        $this->publishManager->flush()->shouldBeCalled();

        $this->subscriber->handleRemove($this->removeEvent->reveal());
        $this->subscriber->handleFlush($this->flushEvent->reveal());

    }
}
