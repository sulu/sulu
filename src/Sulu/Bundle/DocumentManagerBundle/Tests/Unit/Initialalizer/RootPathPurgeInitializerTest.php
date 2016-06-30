<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Initialalizer;

use Doctrine\Common\Persistence\ConnectionRegistry;
use PHPCR\NodeInterface;
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\RootPathPurgeInitializer;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Symfony\Component\Console\Output\OutputInterface;

class RootPathPurgeInitializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface
     */
    private $session1;

    /**
     * @var SessionInterface
     */
    private $session2;

    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    /**
     * @var PathSegmentRegistry
     */
    private $segmentRegistry;

    /**
     * @var RootPathPurgeInitializer
     */
    private $rootPathPurgeInitializer;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var NodeInterface
     */
    private $node;

    public function setUp()
    {
        $this->session1 = $this->prophesize(SessionInterface::class);
        $this->session2 = $this->prophesize(SessionInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->connectionRegistry = $this->prophesize(ConnectionRegistry::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->segmentRegistry = $this->prophesize(PathSegmentRegistry::class);
        $this->rootPathPurgeInitializer = new RootPathPurgeInitializer(
            $this->connectionRegistry->reveal(),
            $this->segmentRegistry->reveal()
        );
    }

    /**
     * It should purge the root path of all sessions.
     */
    public function testPurgeRootNodes()
    {
        $this->connectionRegistry->getConnections()->willReturn([
            $this->session1->reveal(),
            $this->session2->reveal(),
        ]);
        $this->segmentRegistry->getPathSegment('root')->willReturn('baff');

        // we attempt to access the root node only to assert that the workspace
        // exists.
        $this->session1->getRootNode()->shouldBeCalled();
        $this->session2->getRootNode()->shouldBeCalled();

        $this->session1->nodeExists('/baff')->willReturn(false);
        $this->session2->nodeExists('/baff')->willReturn(true);
        $this->session2->getNode('/baff')->willReturn($this->node->reveal());
        $this->node->remove()->shouldBeCalled();
        $this->session2->save()->shouldBeCalled();

        $this->rootPathPurgeInitializer->initialize($this->output->reveal(), true);
    }

    /**
     * It should skip purging if the workspace does not exist.
     */
    public function testDoNotPurgeWorkspaceNotFound()
    {
        $this->connectionRegistry->getConnections()->willReturn([
            $this->session1->reveal(),
            $this->session2->reveal(),
        ]);
        $this->segmentRegistry->getPathSegment('root')->willReturn('baff');

        // throw a repository exception because the workspace does not exist
        $this->session1->getRootNode()->willThrow(new RepositoryException('Foo'));

        // session 2 is fine however
        $this->session2->getRootNode()->shouldBeCalled();

        $this->session1->nodeExists('/baff')->shouldNotBeCalled();
        $this->session2->nodeExists('/baff')->willReturn(true);
        $this->session2->getNode('/baff')->willReturn($this->node->reveal());
        $this->node->remove()->shouldBeCalled();
        $this->session2->save()->shouldBeCalled();

        $this->rootPathPurgeInitializer->initialize($this->output->reveal(), true);
    }

    public function testInitializeWithFalsePurgeFlag()
    {
        $this->connectionRegistry->getConnections()->shouldNotBeCalled();
        $this->rootPathPurgeInitializer->initialize($this->output->reveal(), false);
    }
}
