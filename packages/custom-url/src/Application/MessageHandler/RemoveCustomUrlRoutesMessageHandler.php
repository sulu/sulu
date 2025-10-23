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

namespace Sulu\CustomUrl\Application\MessageHandler;

use Sulu\CustomUrl\Application\Messages\RemoveCustomUrlRoutesMessage;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRouteRepositoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class RemoveCustomUrlRoutesMessageHandler
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private CustomUrlRouteRepositoryInterface $customUrlRouteRepository,
    ) {
    }

    public function __invoke(RemoveCustomUrlRoutesMessage $message): void
    {
        // Verify the custom URL exists and belongs to the webspace
        $customUrl = $this->customUrlRepository->findOneBy([
            'uuid' => $message->getCustomUrlId(),
            'webspace' => $message->getWebspaceKey(),
        ]);

        if (null === $customUrl) {
            throw new AccessDeniedException(\sprintf(
                'Custom URL with ID "%s" not found in webspace "%s"',
                $message->getCustomUrlId(),
                $message->getWebspaceKey()
            ));
        }

        $routeIds = $message->getRouteIds();

        foreach ($routeIds as $routeId) {
            $route = $this->customUrlRouteRepository->findOneBy([
                'uuid' => $routeId,
                'customUrl' => $customUrl->getUuid(),
            ]);

            // Only delete routes that are history routes
            if (null !== $route && $route->isHistory()) {
                $this->customUrlRouteRepository->remove($route);
            }
        }

        // Flush is handled outside by EnableFlushStamp
    }
}
