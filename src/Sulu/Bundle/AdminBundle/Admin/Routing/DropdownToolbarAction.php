<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

class DropdownToolbarAction extends ToolbarAction
{
    public function __construct(string $label, string $icon, array $actions)
    {
        parent::__construct(
            'sulu_admin.dropdown',
            [
                'label' => $label,
                'icon' => $icon,
                'actions' => $actions,
            ]
        );
    }
}
