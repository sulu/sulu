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

class SaveAndCreateToolbarAction extends ToolbarAction
{
    /**
     * @param string $label Translation key of the label, translated on the server
     * @param string $title Translation key of the dialog title, translated on the server
     * @param array<string, mixed>|null $data
     * @param array<string, mixed>|null $parameters
     */
    public function __construct(
        string $label,
        string $title,
        string $formKey,
        string $resourceKey,
        string $idParameter,
        ?array $data = null,
        ?array $parameters = null,
        ?string $localeParameter = null,
        ?string $visibleCondition = null,
        ?string $permission = null
    ) {
        $options = [
            'label' => $label,
            'title' => $title,
            'formKey' => $formKey,
            'resourceKey' => $resourceKey,
            'idParameter' => $idParameter,
            'data' => $data,
            'parameters' => $parameters,
            'localeParameter' => $localeParameter,
            'visible_condition' => $visibleCondition,
            'permission' => $permission,
        ];

        parent::__construct(
            'sulu_admin.save_and_create',
            \array_filter($options, static fn ($option) => null !== $option)
        );
    }
}
