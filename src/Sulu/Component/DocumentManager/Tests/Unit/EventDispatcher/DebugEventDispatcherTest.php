<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Sulu\Component\DocumentManager\Event\AbstractDocumentEvent;
use Sulu\Component\DocumentManager\EventDispatcher\DebugEventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

final class DebugEventDispatcherTest extends TestCase
{
    public const TEST = 'test';

    /**
     * @var DebugEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var array<string>
     */
    protected $logOut = [];

    protected function setUp(): void
    {
        $this->dispatcher = new DebugEventDispatcher(new Stopwatch(), new class($this->logOut) extends AbstractLogger {
            /**
             * @param array<string> $logOutput
             */
            public function __construct(protected array &$logOutput)
            {
            }

            public function log($level, $message, array $context = []): void
            {
                $this->logOutput[] = $message;
            }

            /**
             * @param object $event
             * @param string $eventName
             * @param mixed $ref
             */
            public function listenerMock($event, $eventName, $ref): void
            {
            }
        });
        $this->dispatcher->addListener(self::TEST, [$this->dispatcher, 'listenerMock']);
    }

    protected function tearDown(): void
    {
        $this->logOut = [];
    }

    public function testDebugLogWritten(): void
    {
        $this->dispatcher->dispatch(new class() extends AbstractDocumentEvent {
            public function __construct()
            {
                parent::__construct(new \stdClass());
            }
        }, self::TEST);
        $this->assertCount(1, $this->logOut);
    }
}
