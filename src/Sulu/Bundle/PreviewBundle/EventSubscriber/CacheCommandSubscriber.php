<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\EventSubscriber;

use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheCommandSubscriber implements EventSubscriberInterface
{
    /**
     * @var KernelFactoryInterface
     */
    private $kernelFactory;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var Application|null
     */
    private $application;

    public function __construct(
        KernelFactoryInterface $kernelFactory,
        string $environment
    ) {
        $this->kernelFactory = $kernelFactory;
        $this->environment = $environment;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => [
                ['onCommand', 0],
            ],
        ];
    }

    public function onCommand(ConsoleCommandEvent $event)
    {
        if (!in_array($event->getCommand()->getName(), [
            CacheClearCommand::getDefaultName(),
            CacheWarmupCommand::getDefaultName(),
        ])) {
            return;
        }

        $previewKernel = $this->kernelFactory->create($this->environment);

        $application = $this->application ?: new Application($previewKernel);
        $application->run($event->getInput(), $event->getOutput());
    }

    /**
     * @internal
     *
     * Needed for testing
     *
     * @see Sulu\Bundle\PreviewBundle\Tests\Unit\EventSubscriber\CacheCommandSubscriberTest
     */
    public function setApplication(Application $application): void
    {
        $this->application = $application;
    }
}
