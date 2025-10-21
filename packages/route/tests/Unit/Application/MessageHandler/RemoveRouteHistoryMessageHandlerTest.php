<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Application\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Route\Application\Message\RemoveRouteHistoryMessage;
use Sulu\Route\Application\MessageHandler\RemoveRouteHistoryMessageHandler;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

#[CoversClass(RemoveRouteHistoryMessageHandler::class)]
class RemoveRouteHistoryMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    private RemoveRouteHistoryMessageHandler $handler;

    public function setUp(): void
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        $this->handler = new RemoveRouteHistoryMessageHandler($this->routeRepository->reveal());
    }

    public function testGetIdentifier(): void
    {
        $message = $this->createMessage(['id' => 2]);

        $route = new Route(
            Route::HISTORY_RESOURCE_KEY,
            'example::1c9b7651-3c44-400c-b37f-df9f9ca533a3',
            'en',
            '/test',
        );

        $this->routeRepository->getOneBy([
            'id' => 2,
            'resourceKey' => Route::HISTORY_RESOURCE_KEY,
        ])->willReturn($route);

        $this->routeRepository->remove($route)
            ->shouldBeCalled();

        $this->assertSame($route, $this->handler->__invoke($message));
    }

    /**
     * @param array{id?: int} $identifier
     */
    private function createMessage(
        array $identifier = ['id' => 1],
    ): RemoveRouteHistoryMessage {
        return new RemoveRouteHistoryMessage($identifier);
    }
}
