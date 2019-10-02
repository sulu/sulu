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

class ViewBuilderFactory implements ViewBuilderFactoryInterface
{
    public function createViewBuilder(string $name, string $path, string $type): ViewBuilderInterface
    {
        return new ViewBuilder($name, $path, $type);
    }

    public function createListViewBuilder(string $name, string $path): ListViewBuilderInterface
    {
        return new ListViewBuilder($name, $path);
    }

    public function createFormOverlayListViewBuilder(string $name, string $path): FormOverlayListViewBuilderInterface
    {
        return new FormOverlayListViewBuilder($name, $path);
    }

    public function createFormViewBuilder(string $name, string $path): FormViewBuilderInterface
    {
        return new FormViewBuilder($name, $path);
    }

    public function createResourceTabViewBuilder(string $name, string $path): ResourceTabViewBuilderInterface
    {
        return new ResourceTabViewBuilder($name, $path);
    }

    public function createTabViewBuilder(string $name, string $path): TabViewBuilderInterface
    {
        return new TabViewBuilder($name, $path);
    }

    public function createPreviewFormViewBuilder(string $name, string $path): PreviewFormViewBuilderInterface
    {
        return new PreviewFormViewBuilder($name, $path);
    }
}
