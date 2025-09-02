<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\Infrastructure\Sulu\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Route\Infrastructure\Sulu\Admin\RouteAdmin;

#[CoversClass(RouteAdmin::class)]
class RouteAdminTest extends KernelTestCase
{
    private RouteAdmin $routeAdmin;

    public function setUp(): void
    {
        $this->routeAdmin = static::getContainer()->get('sulu_route.route_admin');
    }

    public function testGetConfigKey(): void
    {
        $this->assertSame('sulu_route', $this->routeAdmin->getConfigKey());
    }

    public function testGetConfig(): void
    {
        $this->assertSame([
            'generateUrl' => '/admin/api/resource-locators',
        ], $this->routeAdmin->getConfig());
    }
}
