<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command;

use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Command\FixturesLoadCommand;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureLoader;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FixturesLoadCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentFixtureLoader
     */
    private $loader;

    /**
     * @var DocumentExecutor
     */
    private $executor;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var BundleInterface
     */
    private $fixtures;

    /**
     * @var DocumentFixtureInterface
     */
    private $fixture1;

    /**
     * @var FixturesLoadCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function setUp()
    {
        $this->loader = $this->prophesize(DocumentFixtureLoader::class);
        $this->executor = $this->prophesize(DocumentExecutor::class);
        $this->kernel = $this->prophesize(KernelInterface::class);
        $this->fixtures = $this->prophesize(BundleInterface::class);
        $this->fixture1 = $this->prophesize(DocumentFixtureInterface::class);

        $application = new Application();
        $application->add(new FixturesLoadCommand(
            $this->loader->reveal(),
            $this->executor->reveal(),
            $this->kernel->reveal()
        ));
        $this->command = $application->find('sulu:document:fixtures:load');
        $this->commandTester = new CommandTester($this->command);

        $this->kernel->getBundles()->willReturn([
            $this->fixtures->reveal(),
        ]);
        $this->fixtures->getPath()->willReturn(
            __DIR__ . '/fixtures'
        );
    }

    /**
     * It should show a message if no fixtures are found.
     */
    public function testNoFixtures()
    {
        $this->kernel->getBundles()->willReturn([]);
        $tester = $this->execute([
            '--no-interaction' => true,
        ]);
        $this->assertContains('Could not find any candidate fixture paths', $tester->getDisplay());
    }

    /**
     * It should load fixtures.
     */
    public function testLoadFixtures()
    {
        $this->loader->load([
            __DIR__ . '/fixtures/DataFixtures/Document',
        ])->willReturn([
            $this->fixture1->reveal(),
        ]);

        $this->executor->execute(
            [
                $this->fixture1->reveal(),
            ],
            true,
            true,
            Argument::type(OutputInterface::class)
        )->shouldBeCalled();

        $tester = $this->execute([
        ]);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * It should not purge the database when --purge is given.
     */
    public function testLoadFixturesAppend()
    {
        $this->loader->load([
            __DIR__ . '/fixtures/DataFixtures/Document',
        ])->willReturn([
            $this->fixture1->reveal(),
        ]);
        $this->executor->execute(
            [
                $this->fixture1->reveal(),
            ],
            false,
            true,
            Argument::type(OutputInterface::class)
        )->shouldBeCalled();

        $tester = $this->execute([
            '--append' => true,
        ]);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * It should not initialize when --no-initialize is specified.
     */
    public function testLoadFixturesNoInitialize()
    {
        $this->loader->load([
            __DIR__ . '/fixtures/DataFixtures/Document',
        ])->willReturn([
            $this->fixture1->reveal(),
        ]);
        $this->executor->execute(
            [
                $this->fixture1->reveal(),
            ],
            true,
            false,
            Argument::type(OutputInterface::class)
        )->shouldBeCalled();

        $tester = $this->execute([
            '--no-initialize' => true,
        ]);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * It should load specified fixtures.
     */
    public function testLoadSpecified()
    {
        $this->loader->load([
            __DIR__ . '/foo',
        ])->willReturn([
            $this->fixture1->reveal(),
        ]);

        $this->executor->execute(
            [
                $this->fixture1->reveal(),
            ],
            true,
            true,
            Argument::type(OutputInterface::class)
        )->shouldBeCalled();

        $tester = $this->execute([
            '--fixtures' => [__DIR__ . '/foo'],
        ]);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * It should show a message if no fixtures are found.
     */
    public function testNoFixturesInteraction()
    {
        $helper = $this->prophesize(QuestionHelper::class);
        $helper->setHelperSet(Argument::cetera())->willReturn(null);
        $helper->getName()->willReturn('question');
        $helper->ask(Argument::cetera())->shouldBeCalled()->willReturn(true);

        $this->command->getHelperSet()->set($helper->reveal(), 'question');

        $this->kernel->getBundles()->willReturn([]);
        $tester = $this->execute([], true);
        $this->assertContains('Could not find any candidate fixture paths', $tester->getDisplay());
    }

    private function execute(array $args, $interactive = false)
    {
        $this->commandTester->execute($args, [
            'interactive' => $interactive,
        ]);

        return $this->commandTester;
    }
}
