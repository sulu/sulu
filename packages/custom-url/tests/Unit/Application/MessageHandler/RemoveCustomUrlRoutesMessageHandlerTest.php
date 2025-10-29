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

namespace Sulu\CustomUrl\Tests\Unit\Application\MessageHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlRoutesMessageHandler;
use Sulu\CustomUrl\Application\Messages\RemoveCustomUrlRoutesMessage;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRouteRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RemoveCustomUrlRoutesMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<CustomUrlRepositoryInterface> */
    private ObjectProphecy $customUrlRepository;

    /** @var ObjectProphecy<CustomUrlRouteRepositoryInterface> */
    private ObjectProphecy $customUrlRouteRepository;

    private RemoveCustomUrlRoutesMessageHandler $handler;

    protected function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->customUrlRouteRepository = $this->prophesize(CustomUrlRouteRepositoryInterface::class);

        $this->handler = new RemoveCustomUrlRoutesMessageHandler(
            $this->customUrlRepository->reveal(),
            $this->customUrlRouteRepository->reveal()
        );
    }

    public function testRemoveSingleHistoryRoute(): void
    {
        $customUrlId = 'custom-url-123';
        $webspaceKey = 'sulu_io';
        $routeId = 'route-456';

        $customUrl = $this->prophesize(CustomUrl::class);
        $customUrl->getUuid()->willReturn($customUrlId);

        $route = $this->prophesize(CustomUrlRoute::class);
        $route->isHistory()->willReturn(true);

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn($customUrl->reveal());

        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $routeId,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn($route->reveal());

        $this->customUrlRouteRepository->remove($route->reveal())
            ->shouldBeCalledOnce();

        $message = new RemoveCustomUrlRoutesMessage($customUrlId, $webspaceKey, [$routeId]);

        $this->handler->__invoke($message);
    }

    public function testRemoveMultipleHistoryRoutes(): void
    {
        $customUrlId = 'custom-url-123';
        $webspaceKey = 'sulu_io';
        $routeIds = ['route-1', 'route-2', 'route-3'];

        $customUrl = $this->prophesize(CustomUrl::class);
        $customUrl->getUuid()->willReturn($customUrlId);

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn($customUrl->reveal());

        foreach ($routeIds as $routeId) {
            $route = $this->prophesize(CustomUrlRoute::class);
            $route->isHistory()->willReturn(true);

            $this->customUrlRouteRepository->findOneBy([
                'uuid' => $routeId,
                'customUrl' => $customUrlId,
            ])->shouldBeCalledOnce()->willReturn($route->reveal());

            $this->customUrlRouteRepository->remove($route->reveal())
                ->shouldBeCalledOnce();
        }

        $message = new RemoveCustomUrlRoutesMessage($customUrlId, $webspaceKey, $routeIds);

        $this->handler->__invoke($message);
    }

    public function testDoesNotRemoveCurrentRoute(): void
    {
        $customUrlId = 'custom-url-123';
        $webspaceKey = 'sulu_io';
        $routeId = 'route-456';

        $customUrl = $this->prophesize(CustomUrl::class);
        $customUrl->getUuid()->willReturn($customUrlId);

        $route = $this->prophesize(CustomUrlRoute::class);
        $route->isHistory()->willReturn(false); // Current route

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn($customUrl->reveal());

        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $routeId,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn($route->reveal());

        // Should NOT call remove() for current route
        $this->customUrlRouteRepository->remove(Argument::any())
            ->shouldNotBeCalled();

        $message = new RemoveCustomUrlRoutesMessage($customUrlId, $webspaceKey, [$routeId]);

        $this->handler->__invoke($message);
    }

    public function testRemoveMixedCurrentAndHistoryRoutes(): void
    {
        $customUrlId = 'custom-url-123';
        $webspaceKey = 'sulu_io';
        $currentRouteId = 'current-route';
        $historyRouteId1 = 'history-route-1';
        $historyRouteId2 = 'history-route-2';

        $customUrl = $this->prophesize(CustomUrl::class);
        $customUrl->getUuid()->willReturn($customUrlId);

        // Current route - should NOT be deleted
        $currentRoute = $this->prophesize(CustomUrlRoute::class);
        $currentRoute->isHistory()->willReturn(false);

        // History routes - should be deleted
        $historyRoute1 = $this->prophesize(CustomUrlRoute::class);
        $historyRoute1->isHistory()->willReturn(true);

        $historyRoute2 = $this->prophesize(CustomUrlRoute::class);
        $historyRoute2->isHistory()->willReturn(true);

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn($customUrl->reveal());

        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $currentRouteId,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn($currentRoute->reveal());

        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $historyRouteId1,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn($historyRoute1->reveal());

        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $historyRouteId2,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn($historyRoute2->reveal());

        // Only history routes should be removed
        $this->customUrlRouteRepository->remove($historyRoute1->reveal())
            ->shouldBeCalledOnce();
        $this->customUrlRouteRepository->remove($historyRoute2->reveal())
            ->shouldBeCalledOnce();

        $message = new RemoveCustomUrlRoutesMessage(
            $customUrlId,
            $webspaceKey,
            [$currentRouteId, $historyRouteId1, $historyRouteId2]
        );

        $this->handler->__invoke($message);
    }

    public function testRemoveWithEmptyRouteIds(): void
    {
        $customUrlId = 'custom-url-123';
        $webspaceKey = 'sulu_io';

        $customUrl = $this->prophesize(CustomUrl::class);

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn($customUrl->reveal());

        // Should not call findOneBy or remove on routes
        $this->customUrlRouteRepository->findOneBy(Argument::any())
            ->shouldNotBeCalled();
        $this->customUrlRouteRepository->remove(Argument::any())
            ->shouldNotBeCalled();

        $message = new RemoveCustomUrlRoutesMessage($customUrlId, $webspaceKey, []);

        $this->handler->__invoke($message);
    }

    public function testRemoveWithNonExistentRouteIds(): void
    {
        $customUrlId = 'custom-url-123';
        $webspaceKey = 'sulu_io';
        $validRouteId = 'valid-route';
        $invalidRouteId = 'invalid-route';

        $customUrl = $this->prophesize(CustomUrl::class);
        $customUrl->getUuid()->willReturn($customUrlId);

        $validRoute = $this->prophesize(CustomUrlRoute::class);
        $validRoute->isHistory()->willReturn(true);

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn($customUrl->reveal());

        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $validRouteId,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn($validRoute->reveal());

        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $invalidRouteId,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn(null);

        // Only valid route should be removed
        $this->customUrlRouteRepository->remove($validRoute->reveal())
            ->shouldBeCalledOnce();

        $message = new RemoveCustomUrlRoutesMessage(
            $customUrlId,
            $webspaceKey,
            [$validRouteId, $invalidRouteId]
        );

        $this->handler->__invoke($message);
    }

    public function testThrowsExceptionForNonExistentCustomUrl(): void
    {
        $customUrlId = 'non-existent-id';
        $webspaceKey = 'sulu_io';

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn(null);

        // Should not call route repository
        $this->customUrlRouteRepository->findOneBy(Argument::any())
            ->shouldNotBeCalled();
        $this->customUrlRouteRepository->remove(Argument::any())
            ->shouldNotBeCalled();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Custom URL with ID "non-existent-id" not found in webspace "sulu_io"');

        $message = new RemoveCustomUrlRoutesMessage($customUrlId, $webspaceKey, ['route-1']);

        $this->handler->__invoke($message);
    }

    public function testThrowsExceptionForWrongWebspace(): void
    {
        $customUrlId = 'custom-url-123';
        $wrongWebspace = 'wrong_webspace';

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $wrongWebspace,
        ])->shouldBeCalledOnce()->willReturn(null);

        // Should not call route repository
        $this->customUrlRouteRepository->findOneBy(Argument::any())
            ->shouldNotBeCalled();
        $this->customUrlRouteRepository->remove(Argument::any())
            ->shouldNotBeCalled();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Custom URL with ID "custom-url-123" not found in webspace "wrong_webspace"');

        $message = new RemoveCustomUrlRoutesMessage($customUrlId, $wrongWebspace, ['route-1']);

        $this->handler->__invoke($message);
    }

    public function testDoesNotDeleteRoutesFromDifferentCustomUrl(): void
    {
        $customUrlId = 'custom-url-123';
        $webspaceKey = 'sulu_io';
        $routeId = 'route-789';

        $customUrl = $this->prophesize(CustomUrl::class);
        $customUrl->getUuid()->willReturn($customUrlId);

        $this->customUrlRepository->findOneBy([
            'uuid' => $customUrlId,
            'webspace' => $webspaceKey,
        ])->shouldBeCalledOnce()->willReturn($customUrl->reveal());

        // Route not found because it belongs to different custom URL (repository filter)
        $this->customUrlRouteRepository->findOneBy([
            'uuid' => $routeId,
            'customUrl' => $customUrlId,
        ])->shouldBeCalledOnce()->willReturn(null);

        // Should NOT call remove() because route was not found
        $this->customUrlRouteRepository->remove(Argument::any())
            ->shouldNotBeCalled();

        $message = new RemoveCustomUrlRoutesMessage($customUrlId, $webspaceKey, [$routeId]);

        $this->handler->__invoke($message);
    }
}
