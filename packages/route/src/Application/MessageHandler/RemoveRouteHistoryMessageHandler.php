<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\MessageHandler;

use Sulu\Route\Application\Message\RemoveRouteHistoryMessage;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveRouteHistoryMessageHandler
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
    ) {
    }

    public function __invoke(RemoveRouteHistoryMessage $message): Route
    {
        $route = $this->routeRepository->getOneBy([
            ...$message->getIdentifier(),
            'resourceKey' => Route::HISTORY_RESOURCE_KEY,
        ]);

        $this->routeRepository->remove($route);

        return $route;
    }
}
