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

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\EventSubscriber\CacheCommandSubscriber;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CacheCommandSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<KernelFactoryInterface>
     */
    private $kernelFactory;

    /**
     * @var CacheCommandSubscriber
     */
    private $cacheCommandSubscriber;

    /**
     * @var ObjectProphecy<KernelInterface>
     */
    private $previewKernel;

    /**
     * @var ObjectProphecy<Application>
     */
    private $application;

    public function setUp(): void
    {
        $this->application = $this->prophesize(Application::class);
        $this->previewKernel = $this->prophesize(KernelInterface::class);
        $this->kernelFactory = $this->prophesize(KernelFactoryInterface::class);
        $this->cacheCommandSubscriber = new CacheCommandSubscriber($this->kernelFactory->reveal(), 'test');

        $this->cacheCommandSubscriber->setApplication($this->application->reveal());
    }

    public function testEventCacheClear(): void
    {
        $command = $this->prophesize(Command::class);
        $command->getName()->willReturn('cache:clear');

        $this->kernelFactory->create(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->previewKernel->reveal());

        $this->application->setAutoExit(false)->shouldBeCalled();
        $this->application->run(Argument::any(), Argument::any())
            ->shouldBeCalled();

        $this->runCommand($command->reveal());
    }

    public function testEventCacheWarmup(): void
    {
        $command = $this->prophesize(Command::class);
        $command->getName()->willReturn('cache:warmup');

        $this->kernelFactory->create(Argument::any())
            ->shouldBeCalled()
            ->willReturn($this->previewKernel->reveal());

        $this->application->setAutoExit(false)->shouldBeCalled();
        $this->application->run(Argument::any(), Argument::any())
            ->shouldBeCalled();

        $this->runCommand($command->reveal());
    }

    public function testEventOtherCommand(): void
    {
        $command = $this->prophesize(Command::class);
        $command->getName()->willReturn('other:command');

        $this->kernelFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $this->application->run(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->runCommand($command->reveal());
    }

    private function runCommand(Command $command): void
    {
        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);

        $event = new ConsoleCommandEvent($command, $input->reveal(), $output->reveal());

        $this->cacheCommandSubscriber->onCommand($event);
    }
}
