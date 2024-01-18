<?php

declare(strict_types=1);

namespace Sulu\Component\DocumentManager\Tests\Unit\EventDispatcher;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Sulu\Component\DocumentManager\Event\AbstractDocumentEvent;
use Sulu\Component\DocumentManager\EventDispatcher\DebugEventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

final class DebugEventDispatcherTest extends TestCase
{
    public const TEST = 'test';
    protected DebugEventDispatcher $dispatcher;
    protected array $logOut = [];

    protected function setUp(): void
    {
        $this->dispatcher = new DebugEventDispatcher(new Stopwatch(), new class($this->logOut) extends AbstractLogger {
            public function __construct(protected array &$logOutput)
            {
            }

            public function log($level, $message, array $context = []): void
            {
                $this->logOutput[] = $message;
            }
        });
        $this->dispatcher->addListener(self::TEST, [$this, 'listenerMock']);
    }

    public function listenerMock($event, $eventName, $ref)
    {
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
