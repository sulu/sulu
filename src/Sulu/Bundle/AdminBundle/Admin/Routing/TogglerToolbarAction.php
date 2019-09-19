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

class TogglerToolbarAction extends ToolbarAction
{
    public function __construct(string $label, string $property, string $activateAction, string $deactivateAction)
    {
        parent::__construct(
            'sulu_admin.toggler',
            [
                'label' => $label,
                'property' => $property,
                'activate' => $activateAction,
                'deactivate' => $deactivateAction,
            ]
        );
    }
}
