<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\TabViewBuilder;

class TabViewBuilderTest extends TestCase
{
    public function testBuildTabViewWithClone()
    {
        $viewBuilder = (new TabViewBuilder('sulu_role.add_form', '/roles'));

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function provideBuildTabView()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
            ],
        ];
    }

    /**
     * @dataProvider provideBuildTabView
     */
    public function testBuildTabView(
        string $name,
        string $path
    ) {
        $viewBuilder = (new TabViewBuilder($name, $path));
        $view = $viewBuilder->getView();

        $this->assertSame($name, $view->getName());
        $this->assertSame($path, $view->getPath());
        $this->assertSame('sulu_admin.tabs', $view->getType());
    }

    public function testBuildFormWithParent()
    {
        $view = (new TabViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption()
    {
        $view = (new TabViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }
}
