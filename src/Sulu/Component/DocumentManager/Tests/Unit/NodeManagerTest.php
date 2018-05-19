<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;
use Sulu\Component\DocumentManager\NodeManager;

class NodeManagerTest extends \PHPUnit_Framework_TestCase
{
    const UUID1 = '0dd2270d-c1e1-4d4e-9b7c-6da0efb6e91d';

    const PATH1 = '/path/to';

    const UUID2 = '1dd2270d-c1e1-4d4e-9b7c-6da0efb6e91d';

    const PATH2 = '/path/to/this';

    /**
     * @var NodeManager
     */
    private $manager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var WorkspaceInterface
     */
    private $workspace;

    /**
     * @var NodeInterface
     */
    private $node1;

    /**
     * @var NodeInterface
     */
    private $node2;

    public function setUp()
    {
        $this->session = $this->prophesize(SessionInterface::class);
        $this->workspace = $this->prophesize(WorkspaceInterface::class);
        $this->manager = new NodeManager(
            $this->session->reveal()
        );

        $this->node1 = $this->prophesize(NodeInterface::class);
        $this->node2 = $this->prophesize(NodeInterface::class);

        $this->session->getWorkspace()->willReturn($this->workspace->reveal());
    }

    /**
     * It should be able to find a node1 by UUID1.
     */
    public function testFindByUuid()
    {
        $this->session->getNodeByIdentifier(self::UUID1)->willReturn($this->node1->reveal());
        $node1 = $this->manager->find(self::UUID1);
        $this->assertSame($this->node1->reveal(), $node1);
    }

    /**
     * It should be able to find a node1 by path.
     */
    public function testFindByPath()
    {
        $this->session->getNode(self::PATH1)->willReturn($this->node1->reveal());
        $node1 = $this->manager->find(self::PATH1);
        $this->assertSame($this->node1->reveal(), $node1);
    }

    /**
     * It should throw an exception if the node1 was not found.
     *
     * @expectedException \Sulu\Component\DocumentManager\Exception\DocumentNotFoundException
     */
    public function testFindNotFound()
    {
        $this->session->getNode(self::PATH1)->willThrow(new PathNotFoundException('Not found'));
        $this->manager->find(self::PATH1);
    }

    /**
     * It should be able to remove a document by UUID1.
     */
    public function testRemoveByUUid()
    {
        $this->session->getNodeByIdentifier(self::UUID1)->willReturn($this->node1->reveal());
        $this->node1->getPath()->willReturn(self::PATH1);
        $this->session->removeItem(self::PATH1)->shouldBeCalled();
        $this->manager->remove(self::UUID1);
    }

    /**
     * It should be able to remove by path.
     */
    public function testRemoveByPath()
    {
        $this->session->removeItem(self::PATH1)->shouldBeCalled();
        $this->manager->remove(self::PATH1);
    }

    /**
     * It should be able to copy a node1.
     */
    public function testCopy()
    {
        $this->session->getNodeByIdentifier(self::UUID1)->willReturn($this->node1->reveal());
        $this->node1->getPath()->willReturn(self::PATH1);

        $this->session->getNodeByIdentifier(self::UUID2)->willReturn($this->node2->reveal());
        $this->node2->getPath()->willReturn(self::PATH2);

        $this->workspace->copy(self::PATH1, self::PATH2 . '/foo')->shouldBeCalled();
        $this->manager->copy(self::UUID1, self::UUID2, 'foo');
    }

    /**
     * It should be able to save the session.
     */
    public function testSave()
    {
        $this->session->save()->shouldBeCalled();
        $this->manager->save();
    }

    /**
     * It should clear/reset the PHPCR session.
     */
    public function testClear()
    {
        $this->session->refresh(false)->shouldBeCalled();
        $this->manager->clear();
    }

    /**
     * It should purge the workspace.
     */
    public function testPurgeWorkspace()
    {
        $this->session->getRootNode()->willReturn($this->node1->reveal());
        $this->node1->getProperties()->willReturn([]);
        $this->node1->getNodes()->willReturn([]);

        $this->manager->purgeWorkspace();
    }
}
