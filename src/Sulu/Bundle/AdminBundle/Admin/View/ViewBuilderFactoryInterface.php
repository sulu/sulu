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

interface ViewBuilderFactoryInterface
{
    public function createViewBuilder(string $name, string $path, string $type): ViewBuilderInterface;

    public function createListViewBuilder(string $name, string $path): ListViewBuilderInterface;

    public function createFormOverlayListViewBuilder(string $name, string $path): FormOverlayListViewBuilderInterface;

    public function createFormViewBuilder(string $name, string $path): FormViewBuilderInterface;

    public function createPreviewFormViewBuilder(string $name, string $path): PreviewFormViewBuilderInterface;

    public function createResourceTabViewBuilder(string $name, string $path): ResourceTabViewBuilderInterface;

    public function createTabViewBuilder(string $name, string $path): TabViewBuilderInterface;
}
