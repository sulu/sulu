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

namespace Sulu\CustomUrl\Tests\Unit\Infrastructure\Sulu\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CustomUrlAdmin::class)]
class CustomUrlAdminTest extends KernelTestCase
{
    private CustomUrlAdmin $admin;

    public function setUp(): void
    {
        self::bootKernel();

        $this->admin = $this->getContainer()->get(CustomUrlAdmin::class);
    }

    public function testNoNavigationItems(): void
    {
        $navigationItemCollection = new NavigationItemCollection();
        $this->admin->configureNavigationItems($navigationItemCollection);

        $this->assertEmpty($navigationItemCollection->all());
    }

    public function testViewCount(): void
    {
        $viewCollection = new ViewCollection();
        $this->admin->configureViews($viewCollection);
        $this->assertCount(1, $viewCollection->all());
    }

    public function testGetSecurityContextWithPlaceholder(): void
    {
        $this->assertSame([
            'Sulu' => [
                'Webspaces' => [
                    'sulu.webspaces.#webspace#.custom-urls' => [
                        0 => 'view',
                        1 => 'add',
                        2 => 'edit',
                        3 => 'delete',
                    ],
                ],
            ],
        ], $this->admin->getSecurityContextsWithPlaceholder());
    }

    public function testGetSecurityContext(): void
    {
        $this->assertSame([
            'Sulu' => [
                'Webspaces' => [
                    'sulu.webspaces.sulu_io.custom-urls' => [
                        0 => 'view',
                        1 => 'add',
                        2 => 'edit',
                        3 => 'delete',
                    ],
                ],
            ],
        ], $this->admin->getSecurityContexts());
    }
}
