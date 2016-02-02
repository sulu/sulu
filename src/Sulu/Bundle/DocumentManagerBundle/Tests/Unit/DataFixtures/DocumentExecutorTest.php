<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DataFixtures;

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Component\Console\Output\BufferedOutput;

class DocumentExecutorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->initializer = $this->prophesize(Initializer::class);
        $this->output = new BufferedOutput();
        $this->fixture1 = $this->prophesize(DocumentFixtureInterface::class);

        $this->executer = new DocumentExecutor(
            $this->documentManager->reveal(),
            $this->nodeManager->reveal(),
            $this->initializer->reveal(),
            $this->output
        );
    }

    /**
     * It should purge the workspace if required.
     */
    public function testPurge()
    {
        $this->nodeManager->purgeWorkspace()->shouldBeCalled();
        $this->initializer->initialize($this->output)->shouldNotBeCalled();
        $this->nodeManager->save()->shouldBeCalled();
        $this->executer->execute([], true, false, $this->output);
    }

    /**
     * It should initialize the workspace if required.
     */
    public function testInitialize()
    {
        $this->nodeManager->purgeWorkspace()->shouldNotBeCalled();
        $this->nodeManager->save()->shouldNotBeCalled();
        $this->initializer->initialize($this->output)->shouldBeCalled();
        $this->executer->execute([], false, true, $this->output);
    }

    /**
     * It should execute the fixtures.
     */
    public function testLoadFixtures()
    {
        $this->fixture1->load($this->documentManager->reveal())->shouldBeCalled();
        $this->executer->execute([$this->fixture1->reveal()], false, false, $this->output);
    }
}
