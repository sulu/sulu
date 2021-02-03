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
    public function testAddRequestParameters(): void
    {
        $this->expectNotToPerformAssertions();

        $badge = new Badge('app.notification_count', 'count', 'count != 0');
        $badge->addRequestParameters(['foo' => 'bar', 'bar' => 'baz']);
    }

    public function testAddRouterAttributesToRequest(): void
    {
        $this->expectNotToPerformAssertions();

        $badge = new Badge('app.notification_count');
        $badge->addRequestParameters(['locale', 'id' => 'entityId']);
    }
}
