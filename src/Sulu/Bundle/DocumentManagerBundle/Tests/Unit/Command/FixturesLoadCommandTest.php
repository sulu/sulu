<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Command\FixturesLoadCommand;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentExecutor;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command\DataFixtures\FooFixture;
use Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command\DataFixtures\GroupBarFixture;
use Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command\DataFixtures\GroupFooFixture;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class FixturesLoadCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentExecutor>
     */
    private $executor;

    /**
     * @var ObjectProphecy<DocumentFixtureInterface>
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

    /**
     * @var \ArrayObject
     */
    private $fixtures;

    public function setUp(): void
    {
        $this->executor = $this->prophesize(DocumentExecutor::class);
        $this->fixture1 = $this->prophesize(DocumentFixtureInterface::class);
        $this->fixtures = new \ArrayObject([]);

        $application = new Application();
        $application->add(new FixturesLoadCommand(
            $this->executor->reveal(),
            $this->fixtures
        ));
        $this->command = $application->find('sulu:document:fixtures:load');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * It should show a message if no fixtures are found.
     */
    public function testNoFixtures(): void
    {
        $tester = $this->execute([
            '--no-interaction' => true,
        ]);

        $this->assertStringContainsString('Could not find any fixtures', $tester->getDisplay());
    }

    /**
     * It should load fixtures.
     */
    public function testLoadFixtures(): void
    {
        $this->fixtures->append($this->fixture1->reveal());

        $this->executor->execute(
            [
                $this->fixture1->reveal(),
            ],
            true,
            true,
            Argument::type(OutputInterface::class)
        )->shouldBeCalled();

        $tester = $this->execute([]);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * It should not purge the database when --append is given.
     */
    public function testLoadFixturesAppend(): void
    {
        $this->fixtures->append($this->fixture1->reveal());

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
    public function testLoadFixturesNoInitialize(): void
    {
        $this->fixtures->append($this->fixture1->reveal());

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
    public function testLoadSpecified(): void
    {
        $fooFixture = new FooFixture();
        $this->fixtures->append($fooFixture);

        $this->executor->execute(
            [
                $fooFixture,
            ],
            true,
            true,
            Argument::type(OutputInterface::class)
        )->shouldBeCalled();

        $tester = $this->execute([
            '--group' => ['FooFixture'],
        ]);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * It should load a specified group.
     */
    public function testLoadGroup(): void
    {
        $fooFixture = new GroupBarFixture();
        $fooFixture2 = new GroupFooFixture();
        $this->fixtures->append($fooFixture);
        $this->fixtures->append($fooFixture2);

        $this->executor->execute(
            [
                $fooFixture,
                $fooFixture2,
            ],
            true,
            true,
            Argument::type(OutputInterface::class)
        )->shouldBeCalled();

        $tester = $this->execute([
            '--group' => ['Group 1'],
        ]);
        $this->assertEquals(0, $tester->getStatusCode());
    }

    /**
     * It should show a message if no fixtures are found.
     */
    public function testNoFixturesInteraction(): void
    {
        $helper = $this->prophesize(QuestionHelper::class);
        $helper->setHelperSet(Argument::cetera())->shouldBeCalled();
        $helper->getName()->willReturn('question');
        $helper->ask(Argument::cetera())->shouldBeCalled()->willReturn(true);

        $this->command->getHelperSet()->set($helper->reveal(), 'question');

        $tester = $this->execute([], true);
        $this->assertStringContainsString('Could not find any fixtures', $tester->getDisplay());
    }

    private function execute(array $args, $interactive = false)
    {
        $this->commandTester->execute($args, [
            'interactive' => $interactive,
        ]);

        return $this->commandTester;
    }
}
