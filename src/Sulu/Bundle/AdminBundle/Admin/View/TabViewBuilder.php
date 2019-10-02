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

class TabViewBuilder implements TabViewBuilderInterface
{
    use ViewBuilderTrait;

    const TYPE = 'sulu_admin.tabs';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, static::TYPE);
    }

    public function getView(): View
    {
        return clone $this->view;
    }
}
