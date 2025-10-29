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

namespace Sulu\CustomUrl\Application\Messages;

class RemoveCustomUrlRoutesMessage
{
    /**
     * @param array<string> $routeIds
     */
    public function __construct(
        private string $customUrlId,
        private string $webspaceKey,
        private array $routeIds
    ) {
    }

    public function getCustomUrlId(): string
    {
        return $this->customUrlId;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    /**
     * @return array<string>
     */
    public function getRouteIds(): array
    {
        return $this->routeIds;
    }
}
