<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Admin\View;

use Sulu\Bundle\AdminBundle\Admin\View\SaveAndCreateToolbarAction;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SaveAndCreateToolbarActionTest extends SuluTestCase
{
    public function testSerializer(): void
    {
        $saveAndCreateToolbarAction = new SaveAndCreateToolbarAction(
            'sulu_admin.save',
            'sulu_admin.save',
            'form',
            'tasks',
            'entityId'
        );

        $this->assertEquals(
            '{"type":"sulu_admin.save_and_create","options":{"label":"Save","title":"Save","formKey":"form","resourceKey":"tasks","idParameter":"entityId"}}',
            $this->getContainer()->get('jms_serializer')->serialize($saveAndCreateToolbarAction, 'json')
        );
    }
}
