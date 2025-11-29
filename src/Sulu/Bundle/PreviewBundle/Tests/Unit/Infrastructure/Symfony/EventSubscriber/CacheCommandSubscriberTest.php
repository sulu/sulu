<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Infrastructure\Symfony\EventSubscriber;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Command\InitCommand;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\EventSubscriber\CacheCommandSubscriber;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CacheCommandSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<KernelFactoryInterface> */
    private ObjectProphecy $kernelFactory;

    /** @var ObjectProphecy<KernelInterface> */
    private ObjectProphecy $previewKernel;

    /** @var ObjectProphecy<Application> */
    private ObjectProphecy $application;

    private CacheCommandSubscriber $cacheCommandSubscriber;

    private InputInterface $input;
    private OutputInterface $output;

    public function setUp(): void
    {
        $this->application = $this->prophesize(Application::class);
        $this->previewKernel = $this->prophesize(KernelInterface::class);
        $this->kernelFactory = $this->prophesize(KernelFactoryInterface::class);
        $this->cacheCommandSubscriber = new CacheCommandSubscriber($this->kernelFactory->reveal());
        $this->input = new ArrayInput([]);
        $this->output = new NullOutput();

        $this->cacheCommandSubscriber->setApplication($this->application->reveal());
    }

    #[DataProvider('dataCommandIsRunProvider')]
    public function testCommandIsRun(string $commandClass): void
    {
        $command = $this->prophesize($commandClass);

        $this->kernelFactory->create()
            ->shouldBeCalled()
            ->willReturn($this->previewKernel->reveal());
        $this->application->setAutoExit(false)->shouldBeCalled();
        $this->application->run($this->input, $this->output)->shouldBeCalled();

        $event = new ConsoleCommandEvent($command->reveal(), $this->input, $this->output);

        $this->cacheCommandSubscriber->onCommand($event);
    }

    public static function dataCommandIsRunProvider(): array
    {
        return [
            [CacheClearCommand::class],
            [CacheWarmupCommand::class],
        ];
    }

    public function testOtherCommandsAreNotForwarded(): void
    {
        $command = $this->prophesize(InitCommand::class);

        $this->application->run($this->input, $this->output)->shouldNotBeCalled();

        $event = new ConsoleCommandEvent($command->reveal(), $this->input, $this->output);

        $this->cacheCommandSubscriber->onCommand($event);
    }
}
