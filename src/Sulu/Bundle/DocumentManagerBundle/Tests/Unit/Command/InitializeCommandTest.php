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
use Sulu\Bundle\DocumentManagerBundle\Command\InitializeCommand;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Tester\CommandTester;

class InitializeCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<QuestionHelper>
     */
    private $questionHelper;

    /**
     * @var ObjectProphecy<Initializer>
     */
    private $initializer;

    public function setUp(): void
    {
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->initializer = $this->prophesize(Initializer::class);
        $this->command = new InitializeCommand(
            $this->initializer->reveal(),
            $this->questionHelper->reveal()
        );
    }

    /**
     * It should initialize the workspace.
     */
    public function testInitialize(): void
    {
        $this->initializer->initialize(Argument::type(OutputInterface::class), false)
            ->shouldBeCalled();
        $this->exec();
    }

    /**
     * It should ask for confirmation and purge the workspace.
     */
    public function testPurgeWorkspace(): void
    {
        $this->initializer->initialize(Argument::type(OutputInterface::class), true)
            ->shouldBeCalled();
        $this->questionHelper->ask(
            Argument::type(InputInterface::class),
            Argument::type(OutputInterface::class),
            Argument::type(ConfirmationQuestion::class)
        )->willReturn(true);

        $this->exec([
            '--purge' => true,
        ]);
    }

    /**
     * It should abort if user does not confirm.
     */
    public function testPurgeWorkspaceAbort(): void
    {
        $this->initializer->initialize(Argument::type(OutputInterface::class), Argument::any())
            ->shouldNotBeCalled();
        $this->questionHelper->ask(
            Argument::type(InputInterface::class),
            Argument::type(OutputInterface::class),
            Argument::type(ConfirmationQuestion::class)
        )->willReturn(false);

        $this->exec([
            '--purge' => true,
        ]);
    }

    /**
     * It should not ask if --force is used.
     */
    public function testForceNoAsk(): void
    {
        $this->initializer->initialize(Argument::type(OutputInterface::class), true)
            ->shouldBeCalled();
        $this->questionHelper->ask(
            Argument::type(InputInterface::class),
            Argument::type(OutputInterface::class),
            Argument::type(ConfirmationQuestion::class)
        )->shouldNotBeCalled();

        $this->exec([
            '--purge' => true,
            '--force' => true,
        ]);
    }

    private function exec(array $args = [])
    {
        $tester = new CommandTester($this->command);
        $tester->execute($args);

        return $tester;
    }
}
