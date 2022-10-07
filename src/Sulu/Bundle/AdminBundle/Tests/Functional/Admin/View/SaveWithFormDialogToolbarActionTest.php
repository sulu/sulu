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

use Sulu\Bundle\AdminBundle\Admin\View\SaveWithFormDialogToolbarAction;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SaveWithFormDialogToolbarActionTest extends SuluTestCase
{
    public function testSerializer(): void
    {
        $saveWithFormDialogToolbarAction = new SaveWithFormDialogToolbarAction('sulu_admin.save', 'form');

        $this->assertEquals(
            '{"type":"sulu_admin.save_with_form_dialog","options":{"condition":"true","formKey":"form","title":"Save"}}',
            $this->getContainer()->get('jms_serializer')->serialize($saveWithFormDialogToolbarAction, 'json')
        );
    }

    public function testSerializerWithCondition(): void
    {
        $saveWithFormDialogToolbarAction = new SaveWithFormDialogToolbarAction('sulu_admin.save', 'form', 'flag');

        $this->assertEquals(
            '{"type":"sulu_admin.save_with_form_dialog","options":{"condition":"flag","formKey":"form","title":"Save"}}',
            $this->getContainer()->get('jms_serializer')->serialize($saveWithFormDialogToolbarAction, 'json')
        );
    }
}
