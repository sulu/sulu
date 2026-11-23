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
use Sulu\Bundle\AdminBundle\Admin\View\SaveAndCreateToolbarAction;

class SaveAndCreateToolbarActionTest extends TestCase
{
    public function testGetType(): void
    {
        $toolbarAction = new SaveAndCreateToolbarAction('app.label', 'app.title', 'form', 'tasks', 'entityId');

        $this->assertSame('sulu_admin.save_and_create', $toolbarAction->getType());
    }

    public function testGetOptionsWithRequiredArguments(): void
    {
        $toolbarAction = new SaveAndCreateToolbarAction('app.label', 'app.title', 'form', 'tasks', 'entityId');

        $this->assertSame(
            [
                'label' => 'app.label',
                'title' => 'app.title',
                'formKey' => 'form',
                'resourceKey' => 'tasks',
                'idParameter' => 'entityId',
            ],
            $toolbarAction->getOptions()
        );
    }

    public function testGetOptionsWithAllArguments(): void
    {
        $toolbarAction = new SaveAndCreateToolbarAction(
            'app.label',
            'app.title',
            'form',
            'tasks',
            'entityId',
            ['handlerClass' => 'Handler'],
            ['entityClass' => 'Entity'],
            'localeCode',
            'isDraft',
            'edit'
        );

        $this->assertSame(
            [
                'label' => 'app.label',
                'title' => 'app.title',
                'formKey' => 'form',
                'resourceKey' => 'tasks',
                'idParameter' => 'entityId',
                'data' => ['handlerClass' => 'Handler'],
                'parameters' => ['entityClass' => 'Entity'],
                'localeParameter' => 'localeCode',
                'visible_condition' => 'isDraft',
                'permission' => 'edit',
            ],
            $toolbarAction->getOptions()
        );
    }

    public function testGetOptionsKeepsEmptyArrays(): void
    {
        $toolbarAction = new SaveAndCreateToolbarAction(
            'app.label',
            'app.title',
            'form',
            'tasks',
            'entityId',
            [],
            []
        );

        $this->assertSame([], $toolbarAction->getOptions()['data']);
        $this->assertSame([], $toolbarAction->getOptions()['parameters']);
    }
}
