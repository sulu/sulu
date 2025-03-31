<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\EventSubscriber;

use App\Kernel;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;
use Symfony\Bundle\FrameworkBundle\Command\CacheWarmupCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class CacheCommandSubscriber implements EventSubscriberInterface
{
    /**
     * @var Application|null
     */
    private $application;

    public function __construct(private KernelFactoryInterface $kernelFactory)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => [
                ['onCommand', 0],
            ],
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (null === $command
            || !\in_array(
                $command->getName(),
                [
                    CacheClearCommand::getDefaultName(),
                    CacheWarmupCommand::getDefaultName(),
                ]
            )
        ) {
            return;
        }

        // avoid to clear cache for preview if no \App\Kernel exists
        // can cause an error in test kernels of bundles
        // this can be removed when https://github.com/sulu/sulu/issues/4782 is fixed
        if (!\class_exists(Kernel::class)) {
            return;
        }

        $previewKernel = $this->kernelFactory->create();

        $application = $this->application ?: new Application($previewKernel);
        $application->setAutoExit(false);
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
