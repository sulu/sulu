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
use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;

class ListItemActionTest extends TestCase
{
    public function testGetType(): void
    {
        $toolbarAction = new ListItemAction('sulu_admin.link', ['link_property' => 'url']);

        $this->assertSame('sulu_admin.link', $toolbarAction->getType());
    }

    public function testGetOptions(): void
    {
        $toolbarAction = new ListItemAction('sulu_admin.link', ['link_property' => 'url']);

        $this->assertSame(['link_property' => 'url'], $toolbarAction->getOptions());
    }
}
