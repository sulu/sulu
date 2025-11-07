<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit;

use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;

class TestRoutable implements RoutableInterface
{
    public ?string $name = null;

    public function __construct(
        private ?RouteInterface $route = null,
        private int $id = 1,
        private string $locale = 'de',
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    public function setRoute(RouteInterface $route): void
    {
        $this->route = $route;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
