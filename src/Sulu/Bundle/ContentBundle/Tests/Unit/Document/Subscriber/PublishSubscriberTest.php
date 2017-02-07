<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\SessionInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Document\Subscriber\PublishSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Event\UnpublishEvent;
use Sulu\Component\DocumentManager\NodeHelperInterface;

class PublishSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var NodeHelperInterface
     */
    private $nodeHelper;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var PublishSubscriber
     */
    private $publishSubscriber;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->nodeHelper = $this->prophesize(NodeHelperInterface::class);
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);

        $this->publishSubscriber = new PublishSubscriber(
            $this->liveSession->reveal(),
            $this->nodeHelper->reveal(),
            $this->propertyEncoder->reveal()
        );

        $this->node = $this->prophesize(NodeInterface::class);
    }

    public function testCreateNodeInPublicWorkspaceWithNewNode()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());

        $mixinNodeType1 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType1->getName()->willReturn('mixin1');
        $mixinNodeType2 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType2->getName()->willReturn('mixin2');

        $defaultRootNode = $this->prophesize(NodeInterface::class);
        $defaultCmfNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode->getIdentifier()->willReturn('uuid');
        $defaultCmfNode->getNode('sulu')->willReturn($defaultSuluNode->reveal());
        $defaultRootNode->getNode('cmf')->willReturn($defaultCmfNode->reveal());
        $session = $this->prophesize(SessionInterface::class);
        $session->getRootNode()->willReturn($defaultRootNode->reveal());

        $liveRootNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode->hasNode('sulu')->willReturn(false);
        $liveRootNode->getNode('cmf')->willReturn($liveCmfNode);
        $liveRootNode->hasNode('cmf')->willReturn(true);
        $this->liveSession->getRootNode()->willReturn($liveRootNode->reveal());

        $liveSuluNode = $this->prophesize(NodeInterface::class);
        $liveSuluNode->setMixins(['mix:referenceable'])->shouldBeCalled();
        $liveSuluNode->setProperty('jcr:uuid', 'uuid')->shouldBeCalled();
        $liveCmfNode->addNode('sulu')->willReturn($liveSuluNode->reveal());

        $this->node->isNew()->willReturn(true);
        $this->node->getPath()->willReturn('/cmf/sulu');
        $this->node->getSession()->willReturn($session->reveal());

        $this->liveSession->itemExists('/cmf/sulu')->willReturn(false);

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testCreateNodeInPublicWorkspaceWithOldNodeAndDifferentName()
    {
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->getName()->willReturn('cmf');
        $liveNode->rename('cmf-test')->shouldBeCalled();

        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf');

        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $this->node->getName()->willReturn('cmf-test');
        $this->node->getPath()->willReturn('/cmf-test');
        $this->node->isNew()->willReturn(false);

        $this->liveSession->getNode('/cmf')->willReturn($liveNode);
        $this->liveSession->getRootNode()->shouldNotBeCalled();

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testCreateNodeInPublicWorkspaceWithOldNodeAndSameName()
    {
        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->getName()->willReturn('cmf');
        $liveNode->rename(Argument::cetera())->shouldNotBeCalled();

        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf');

        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());
        $event->getDocument()->willReturn($document->reveal());

        $this->node->getName()->willReturn('cmf');
        $this->node->getPath()->willReturn('/cmf');
        $this->node->isNew()->willReturn(false);

        $this->liveSession->getNode('/cmf')->willReturn($liveNode);
        $this->liveSession->getRootNode()->shouldNotBeCalled();

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testCreateNodeInPublicWorkspaceWithExistingNode()
    {
        $event = $this->prophesize(PersistEvent::class);
        $event->getNode()->willReturn($this->node->reveal());

        $this->node->getPath()->willReturn('/cmf');
        $this->node->isNew()->willReturn(true);

        $this->liveSession->itemExists('/cmf')->willReturn(true);
        $this->liveSession->getRootNode()->shouldNotBeCalled();

        $this->publishSubscriber->createNodeInPublicWorkspace($event->reveal());
    }

    public function testRemoveNodeFromPublicWorkspace()
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(RemoveEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $this->node->remove()->shouldBeCalled();

        $this->publishSubscriber->removeNodeFromPublicWorkspace($event->reveal());
    }

    public function testMoveNodeInPublicWorkspace()
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(MoveEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getDestId()->willReturn('uuid');
        $event->getDestName()->willReturn('name');

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $this->nodeHelper->move($this->node, 'uuid', 'name');

        $this->publishSubscriber->moveNodeInPublicWorkspace($event->reveal());
    }

    public function testCopyNodeInPublicWorkspace()
    {
        $this->node->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(CopyEvent::class);
        $event->getCopiedNode()->willReturn($this->node->reveal());

        $mixinNodeType1 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType1->getName()->willReturn('mixin1');
        $mixinNodeType2 = $this->prophesize(NodeTypeInterface::class);
        $mixinNodeType2->getName()->willReturn('mixin2');

        $defaultRootNode = $this->prophesize(NodeInterface::class);
        $defaultCmfNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode = $this->prophesize(NodeInterface::class);
        $defaultSuluNode->getIdentifier()->willReturn('uuid');
        $defaultCmfNode->getNode('sulu')->willReturn($defaultSuluNode->reveal());
        $defaultRootNode->getNode('cmf')->willReturn($defaultCmfNode->reveal());
        $session = $this->prophesize(SessionInterface::class);
        $session->getRootNode()->willReturn($defaultRootNode->reveal());
        $this->node->getSession()->willReturn($session->reveal());

        $liveRootNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode = $this->prophesize(NodeInterface::class);
        $liveCmfNode->hasNode('sulu')->willReturn(false);
        $liveRootNode->getNode('cmf')->willReturn($liveCmfNode);
        $liveRootNode->hasNode('cmf')->willReturn(true);
        $this->liveSession->getRootNode()->willReturn($liveRootNode->reveal());

        $liveSuluNode = $this->prophesize(NodeInterface::class);
        $liveSuluNode->setMixins(['mix:referenceable'])->shouldBeCalled();
        $liveSuluNode->setProperty('jcr:uuid', 'uuid')->shouldBeCalled();
        $liveCmfNode->addNode('sulu')->willReturn($liveSuluNode->reveal());

        $this->liveSession->itemExists('/cmf/sulu')->willReturn(false);

        $defaultTestNode = $this->prophesize(NodeInterface::class);
        $defaultTestNode->getPath()->willReturn('/cmf/sulu/test');
        $defaultTestNode->getSession()->willReturn($session->reveal());
        $defaultSuluNode->getNode('test')->willReturn($defaultTestNode);
        $defaultTestNode->getIdentifier()->willReturn('test-uuid');
        $defaultTestNode->getNodes()->willReturn([]);
        $this->node->getNodes()->willReturn([$defaultTestNode->reveal()]);

        $liveSuluNode->hasNode('test')->willReturn(false);
        $liveTestNode = $this->prophesize(NodeInterface::class);
        $liveTestNode->setMixins(['mix:referenceable'])->shouldBeCalled();
        $liveTestNode->setProperty('jcr:uuid', 'test-uuid')->shouldBeCalled();
        $liveSuluNode->addNode('test')->willReturn($liveTestNode->reveal());

        $this->liveSession->itemExists('/cmf/sulu/test')->willReturn(false);

        $this->publishSubscriber->copyNodeInPublicWorkspace($event->reveal());
    }

    public function testReorderInPublicWorkspace()
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu_io/contents/page');

        $event = $this->prophesize(ReorderEvent::class);
        $event->getDestId()->willReturn('uuid');
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu_io/contents/page')->willReturn($this->node->reveal());

        $parentNode = $this->prophesize(NodeInterface::class);
        $this->node->getParent()->willReturn($parentNode->reveal());

        $siblingNode = $this->prophesize(NodeInterface::class);
        $parentNode->getNodes()->willReturn([$this->node->reveal(), $siblingNode->reveal()]);

        $this->propertyEncoder->systemName('order')->willReturn('sulu:order');

        $this->nodeHelper->reorder($this->node->reveal(), 'uuid')->shouldBeCalled();

        $this->node->setProperty('sulu:order', 10)->shouldBeCalled();
        $siblingNode->setProperty('sulu:order', 20)->shouldBeCalled();

        $this->publishSubscriber->reorderNodeInPublicWorkspace($event->reveal());
    }

    public function testSetNodeFromPublicWorkspaceForPublishing()
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $event->setNode($this->node->reveal())->shouldBeCalled();

        $this->publishSubscriber->setNodeFromPublicWorkspaceForPublishing($event->reveal());
    }

    public function testSetNodeFromPublicWorkspaceForUnpublishing()
    {
        $document = $this->prophesize(PathBehavior::class);
        $document->getPath()->willReturn('/cmf/sulu');

        $event = $this->prophesize(UnpublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $this->liveSession->getNode('/cmf/sulu')->willReturn($this->node->reveal());

        $event->setNode($this->node->reveal())->shouldBeCalled();

        $this->publishSubscriber->setNodeFromPublicWorkspaceForUnpublishing($event->reveal());
    }

    public function testFlushPublicWorkspace()
    {
        $this->liveSession->save()->shouldBeCalled();

        $this->publishSubscriber->flushPublicWorkspace();
    }
}
