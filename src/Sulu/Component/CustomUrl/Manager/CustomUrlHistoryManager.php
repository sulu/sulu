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

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRouteRemovedEvent;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrlRoute;

class CustomUrlHistoryManager
{
    public function deleteRoute(int $id, string $webspaceKey): CustomUrl
    {
        $customUrl = $this->customUrlRepository->find($id);

        if (!$customUrl->isHistory()) {
            throw new RouteNotRemovableException($customUrl, $customUrl);
        }

        $this->entityManager->remove($customUrl);

        $this->documentDomainEventCollector->collect(new CustomUrlRouteRemovedEvent($customUrl));

        return $customUrl;
    }

    /** @return array<CustomUrlRoute> */
    public function findHistoryRoutesById(string $id, string $webspaceKey): array
    {
        return $this->entityManager->findBy(['route' => $id]);
    }
}
