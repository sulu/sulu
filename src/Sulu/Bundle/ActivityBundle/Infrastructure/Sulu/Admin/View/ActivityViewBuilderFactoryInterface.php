<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View;

use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilderInterface;

interface ActivityViewBuilderFactoryInterface
{
    public function createActivityListViewBuilder(
        string $name,
        string $path,
        string $resourceKey,
        string $resourceIdRouterAttribute = 'id'
    ): ListViewBuilderInterface;

    public function hasActivityListPermission(): bool;
}
