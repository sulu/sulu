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

use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;

class ViewCollection
{
    /**
     * @var ViewBuilderInterface[]
     */
    private $views = [];

    public function add(ViewBuilderInterface $viewBuilder): void
    {
        $this->views[$viewBuilder->getName()] = $viewBuilder;
    }

    public function get(string $viewName): ViewBuilderInterface
    {
        if (!\array_key_exists($viewName, $this->views)) {
            throw new ViewNotFoundException($viewName);
        }

        return $this->views[$viewName];
    }

    public function has(string $viewName): bool
    {
        return \array_key_exists($viewName, $this->views);
    }

    /**
     * @return ViewBuilderInterface[]
     */
    public function all(): array
    {
        return $this->views;
    }

    public function remove(string $viewName): void
    {
        if (!\array_key_exists($viewName, $this->views)) {
            throw new ViewNotFoundException($viewName);
        }

        unset($this->views[$viewName]);
    }
}
