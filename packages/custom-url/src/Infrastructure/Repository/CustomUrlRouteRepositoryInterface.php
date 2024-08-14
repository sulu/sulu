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

namespace Sulu\CustomUrl\Infrastructure\Repository;

use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;

interface CustomUrlRouteRepositoryInterface
{
    public function count(): int;

    /**
     * @return array<CustomUrlRoute>
     */
    public function findByCustomUrl(CustomUrlInterface $customUrl): array;

    /**
     * Get all routes for the current CustomUrl, but skip the newest (because that's the currently in use route).
     *
     * @return array{id: string, resourcelocator: string, created: \DateTimeInterface}
     */
    public function findHistoryRoutes(CustomUrlInterface|string $customUrl): array;

    /**
     * Adds the currently set route in the custom url as a history entry.
     */
    public function addRoute(CustomUrlInterface $customUrl): void;

    /**
     * @param array<string> $ids
     */
    public function deleteAll(array $ids, string $webspaceKey): void;
}
