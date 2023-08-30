<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\EventDispatcher;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Logging and profiling event dispatcher for the document manager.
 */
class DebugEventDispatcher extends EventDispatcher
{
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Stopwatch $stopwatch,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->stopwatch = $stopwatch;
        $this->logger = $logger ?: new NullLogger();
    }

    protected function doDispatch($listeners, $eventName, $event)
    {
        $eventStopwatch = $this->stopwatch->start($eventName, 'section');

        foreach ($listeners as $listener) {
            list($listenerInstance, $methodName) = $listener;
            $className = \get_class($listenerInstance);
            $name = $this->getDebugClassName($className);

            $listenerStopwatch = $this->stopwatch->start($className . '->' . $methodName, 'document_manager_listener');

            \call_user_func($listener, $event, $eventName, $this);

            $this->logger->debug(\sprintf(
                '%-40s%-20s %s', $name, $methodName, $event->getDebugMessage()
            ));

            if ($listenerStopwatch->isStarted()) {
                $listenerStopwatch->stop();
            }

            if ($event->isPropagationStopped()) {
                break;
            }
        }

        if ($eventStopwatch->isStarted()) {
            $eventStopwatch->stop();
        }
    }

    private function getDebugClassName($className)
    {
        $parts = \explode('\\', $className);
        $last = \array_pop($parts);
        $parts = \array_map(function($part) {
            return \substr($part, 0, 1);
        }, $parts);

        return \implode('\\', $parts) . '\\' . $last;
    }
}
