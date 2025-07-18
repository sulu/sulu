<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Sulu\Admin;

use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilderInterface;

interface ContentVersionViewBuilderFactoryInterface
{
    public function createContentVersionListViewBuilder(
        string $name,
        string $path,
        string $resourceKey,
        string $listKey,
        string $successView,
        string $resourceIdRouterAttribute = 'id'
    ): ListViewBuilderInterface;

    public function hasContentVersionListPermission(): bool;
}
