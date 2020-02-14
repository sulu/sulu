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
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;

class ToolbarActionTest extends TestCase
{
    public function testGetType()
    {
        $toolbarAction = new ToolbarAction('sulu_admin.delete', ['allow_conflict_deletion' => false]);

        $this->assertSame('sulu_admin.delete', $toolbarAction->getType());
    }

    public function testGetOptions()
    {
        $toolbarAction = new ToolbarAction('sulu_admin.delete', ['allow_conflict_deletion' => false]);

        $this->assertSame(['allow_conflict_deletion' => false], $toolbarAction->getOptions());
    }
}
