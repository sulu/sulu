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

class ViewBuilder implements ViewBuilderInterface
{
    use ViewBuilderTrait;

    public function __construct(string $name, string $path, string $type)
    {
        $this->view = new View($name, $path, $type);
    }

    public function getView(): View
    {
        return clone $this->view;
    }
}
