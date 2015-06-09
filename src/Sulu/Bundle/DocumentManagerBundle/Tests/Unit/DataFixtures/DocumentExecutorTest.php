<?php

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DataFixtures;

use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Symfony\Component\Console\Output\BufferedOutput;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\FixtureLoader;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;

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
     * It should purge the workspace if required
     */
    public function testPurge()
    {
        $this->nodeManager->purgeWorkspace()->shouldBeCalled();
        $this->initializer->initialize($this->output)->shouldNotBeCalled();
        $this->nodeManager->save()->shouldBeCalled();
        $this->executer->execute(array(), true, false, $this->output);
    }

    /**
     * It should initialize the workspace if required
     */
    public function testInitialize()
    {
        $this->nodeManager->purgeWorkspace()->shouldNotBeCalled();
        $this->nodeManager->save()->shouldNotBeCalled();
        $this->initializer->initialize($this->output)->shouldBeCalled();
        $this->executer->execute(array(), false, true, $this->output);
    }

    /**
     * It should execute the fixtures
     */
    public function testLoadFixtures()
    {
        $this->fixture1->load($this->documentManager->reveal())->shouldBeCalled();
        $this->executer->execute(array($this->fixture1->reveal()), false, false, $this->output);
    }
}
