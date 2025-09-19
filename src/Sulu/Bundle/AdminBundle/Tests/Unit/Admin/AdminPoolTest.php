<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Symfony\Component\Console\Command\Command;

class AdminPoolTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var AdminPool
     */
    protected $adminPool;

    /**
     * @var ObjectProphecy<Admin>
     */
    protected $admin1;

    /**
     * @var ObjectProphecy<Admin>
     */
    protected $admin2;

    /**
     * @var Command
     */
    protected $command;

    public function setUp(): void
    {
        $this->adminPool = new AdminPool();
        $this->admin1 = $this->prophesize(Admin::class);
        $this->admin2 = $this->prophesize(Admin::class);

        $this->adminPool = new AdminPool([$this->admin1->reveal(), $this->admin2->reveal()]);
    }

    public function testAdmins(): void
    {
        $this->assertEquals(2, \count($this->adminPool->getAdmins()));
        $this->assertSame($this->admin1->reveal(), $this->adminPool->getAdmins()[0]);
        $this->assertSame($this->admin2->reveal(), $this->adminPool->getAdmins()[1]);
    }

    public function testSecurityContexts(): void
    {
        $this->admin1->getSecurityContexts()->willReturn([
            'Sulu' => [
                'Assets' => [
                    'assets.videos',
                    'assets.pictures',
                    'assets.documents',
                ],
            ],
        ]);

        $this->admin2->getSecurityContexts()->willReturn([
            'Sulu' => [
                'Portal' => [
                    'portals.com',
                    'portals.de',
                ],
            ],
        ]);

        $contexts = $this->adminPool->getSecurityContexts();

        $this->assertEquals(
            [
                'assets.videos',
                'assets.pictures',
                'assets.documents',
            ],
            $contexts['Sulu']['Assets']
        );
        $this->assertEquals(
            [
                'portals.com',
                'portals.de',
            ],
            $contexts['Sulu']['Portal']
        );
    }
}
