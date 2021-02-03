<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\Badge;

class BadgeTest extends TestCase
{
    public function testGetRouteName(): void
    {
        $badge = new Badge('app.notification_count');
        $this->assertSame('app.notification_count', $badge->getRouteName());
    }

    public function testGetDataPath(): void
    {
        $badge = new Badge('app.notification_count', '/count');
        $this->assertSame('/count', $badge->getDataPath());
    }

    public function testGetVisibleCondition(): void
    {
        $badge = new Badge('app.notification_count', '/count', 'count != 0');
        $this->assertSame('count != 0', $badge->getVisibleCondition());
    }

    public function testAddRequestParameters(): void
    {
        $badge = new Badge('app.notification_count');
        $badge->addRequestParameters(['foo' => 'bar', 'bar' => 'baz']);
        $badge->addRequestParameters(['foo' => 'baz', 'baz' => 'bar']);

        $this->assertEquals([
            'foo' => 'baz',
            'bar' => 'baz',
            'baz' => 'bar',
        ], $badge->getRequestParameters());
    }

    public function testAddRouterAttributesToRequest(): void
    {
        $badge = new Badge('app.notification_count');
        $badge->addRouterAttributesToRequest(['locale', 'id' => 'entityId']);
        $badge->addRouterAttributesToRequest(['id' => 'otherId']);

        $this->assertEquals([
            'locale',
            'id' => 'otherId',
        ], $badge->getRouterAttributesToRequest());
    }
}
