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
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\WorkspaceInitializer;
use Symfony\Component\Console\Output\BufferedOutput;

class WorkspaceInitializerTest extends \PHPUnit_Framework_TestCase
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
     * @var WorkspaceInitializer
     */
    private $initializer;

    /**
     * @var WorkspaceInterface
     */
    private $workspace1;

    /**
     * @var WorkspaceInterface
     */
    private $workspace2;

    public function setUp()
    {
        $this->session1 = $this->prophesize(SessionInterface::class);
        $this->session2 = $this->prophesize(SessionInterface::class);
        $this->connectionRegistry = $this->prophesize(ConnectionRegistry::class);
        $this->workspace1 = $this->prophesize(WorkspaceInterface::class);
        $this->workspace2 = $this->prophesize(WorkspaceInterface::class);
        $this->output = new BufferedOutput();

        $this->initializer = new WorkspaceInitializer(
            $this->connectionRegistry->reveal()
        );
    }

    /**
     * It should create the workspace on connections with non-existing workspaces.
     */
    public function testCreateWorkspace()
    {
        $this->connectionRegistry->getConnections()->willReturn([
            $this->session1->reveal(),
            $this->session2->reveal(),
        ]);

        $this->session1->getWorkspace()->willReturn($this->workspace1->reveal());
        $this->session2->getWorkspace()->willReturn($this->workspace2->reveal());

        $this->workspace1->getName()->willReturn('hello1');
        $this->workspace2->getName()->willReturn('hello2');
        $this->workspace1->createWorkspace('hello1')->willThrow(new RepositoryException('foo'));
        $this->workspace2->createWorkspace('hello2')->shouldBeCalled();

        $this->initializer->initialize($this->output);
    }
}
