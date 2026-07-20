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

namespace Sulu\Content\Domain\Model;

use Sulu\Route\Domain\Model\Route;

interface RoutableInterface
{
    public static function getResourceKey(): string;

    /**
     * Whether this resource requires a route. When false, the resource
     * may be saved and published without a route.
     */
    public static function isRouteMandatory(): bool;

    public function getResourceId(): int|string;

    public function getLocale(): ?string;

    public function setRoute(Route $route): void;

    public function getRoute(): ?Route;
}
