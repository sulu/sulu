<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

class SaveWithFormDialogToolbarAction extends ToolbarAction
{
    public function __construct(string $title, string $formKey, string $condition = 'true')
    {
        parent::__construct(
            'sulu_admin.save_with_form_dialog',
            [
                'condition' => $condition,
                'formKey' => $formKey,
                'title' => $title,
            ]
        );
    }
}
