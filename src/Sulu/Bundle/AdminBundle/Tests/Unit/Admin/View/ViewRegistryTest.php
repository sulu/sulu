<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Admin\View;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\Exception\ParentViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;

class ViewRegistryTest extends TestCase
{
    use ProphecyTrait;
    use ReadObjectAttributeTrait;

    /**
     * @var ViewRegistry
     */
    protected $viewRegistry;

    /**
     * @var ObjectProphecy<AdminPool>
     */
    protected $adminPool;

    /**
     * @var ObjectProphecy<Admin>
     */
    protected $admin1;

    /**
     * @var ObjectProphecy<Admin>
     */
    protected $admin2;

    public function setUp(): void
    {
        $this->admin1 = $this->prophesize(Admin::class);
        $this->admin2 = $this->prophesize(Admin::class);

        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->adminPool->getAdmins()->willReturn([$this->admin1, $this->admin2]);

        $this->viewRegistry = new ViewRegistry($this->adminPool->reveal());
    }

    public function testFindViewByName(): void
    {
        $viewBuilder1 = new ViewBuilder('test1', '/test1', 'test1');
        $viewBuilder1->setOption('value', 'test1');
        $viewBuilder2 = new ViewBuilder('test2', '/test2', 'test2');
        $viewBuilder2->setOption('value', 'test2');
        $viewBuilder3 = new ViewBuilder('test3', '/test3', 'test3');
        $viewBuilder3->setOption('value', 'test3');
        $this->admin1->configureViews(Argument::any())->will(function($arguments) use ($viewBuilder1): void {
            $arguments[0]->add($viewBuilder1);
        });
        $this->admin2->configureViews(Argument::any())->will(
            function($arguments) use ($viewBuilder2, $viewBuilder3): void {
                $arguments[0]->add($viewBuilder2);
                $arguments[0]->add($viewBuilder3);
            }
        );

        $view = $this->viewRegistry->findViewByName('test2');
        $this->assertEquals($view, $viewBuilder2->getView());
    }

    public function testFindViewByNameException(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $this->viewRegistry->findViewByName('not_existing');
    }

    public function testGetViews(): void
    {
        $viewBuilder1 = new ViewBuilder('test1', '/test1', 'test1');
        $viewBuilder1->setOption('value', 'test1');
        $viewBuilder2 = new ViewBuilder('test2', '/test2', 'test2');
        $viewBuilder2->setOption('value', 'test2');
        $viewBuilder3 = new ViewBuilder('test3', '/test3', 'test3');
        $viewBuilder3->setOption('value', 'test3');

        $this->admin1->configureViews(Argument::any())->will(
            function($arguments) use ($viewBuilder1): void {
                $arguments[0]->add($viewBuilder1);
            }
        )->shouldBeCalledTimes(1);
        $this->admin2->configureViews(Argument::any())->will(
            function($arguments) use ($viewBuilder2, $viewBuilder3): void {
                $arguments[0]->add($viewBuilder2);
                $arguments[0]->add($viewBuilder3);
            }
        )->shouldBeCalledTimes(1);

        $views = $this->viewRegistry->getViews();
        $this->assertCount(3, $views);
        $this->assertEquals($viewBuilder1->getView(), $views[0]);
        $this->assertEquals($viewBuilder2->getView(), $views[1]);
        $this->assertEquals($viewBuilder3->getView(), $views[2]);
    }

    public function testGetViewsMemoryCache(): void
    {
        $viewBuilder1 = new ViewBuilder('test1', '/test1', 'test1');
        $viewBuilder1->setOption('value', 'test1');
        $viewBuilder2 = new ViewBuilder('test2', '/test2', 'test2');
        $viewBuilder2->setOption('value', 'test2');
        $viewBuilder3 = new ViewBuilder('test3', '/test3', 'test3');
        $viewBuilder3->setOption('value', 'test3');
        $this->admin1->configureViews(Argument::any())->will(
            function($arguments) use ($viewBuilder1): void {
                $arguments[0]->add($viewBuilder1);
            }
        )->shouldBeCalledTimes(1);
        $this->admin2->configureViews(Argument::any())->will(
            function($arguments) use ($viewBuilder2, $viewBuilder3): void {
                $arguments[0]->add($viewBuilder2);
                $arguments[0]->add($viewBuilder3);
            }
        )->shouldBeCalledTimes(1);

        $views1 = $this->viewRegistry->getViews();
        $views2 = $this->viewRegistry->getViews();

        $this->assertSame($views1, $views2);
    }

    public function testViewWithNonExistingParent(): void
    {
        $this->expectException(ParentViewNotFoundException::class);

        $viewBuilder = new ViewBuilder('test1', '/test1', 'test1');
        $viewBuilder->setParent('not-existing');
        $this->admin1->configureViews(Argument::any())->will(function($arguments) use ($viewBuilder): void {
            $arguments[0]->add($viewBuilder);
        });

        $this->viewRegistry->getViews();
    }

    public function testViewsMergeOptions(): void
    {
        $viewBuilder1 = new ViewBuilder('test1', '/test1', 'test1');
        $viewBuilder1->setOption('view1', 'test1');
        $viewBuilder1->setOption('override', 'override');
        $viewBuilder1_1 = new ViewBuilder('test1_1', '/test1_1', 'test1_1');
        $viewBuilder1_1->setOption('view1_1', 'test1_1');
        $viewBuilder1_1->setParent('test1');
        $viewBuilder1_1_1 = new ViewBuilder('test1_1_1', '/test1_1_1', 'test1_1_1');
        $viewBuilder1_1_1->setOption('override', 'overriden-value');
        $viewBuilder1_1_1->setOption('view1_1_1', 'test1_1_1');
        $viewBuilder1_1_1->setParent('test1_1');
        $viewBuilder2 = new ViewBuilder('test2', '/test2', 'test2');
        $viewBuilder2->setOption('value', 'test');

        $this->admin1->configureViews(Argument::any())->will(
            function($arguments) use ($viewBuilder1, $viewBuilder1_1, $viewBuilder1_1_1, $viewBuilder2): void {
                $arguments[0]->add($viewBuilder1);
                $arguments[0]->add($viewBuilder1_1);
                $arguments[0]->add($viewBuilder1_1_1);
                $arguments[0]->add($viewBuilder2);
            }
        );

        $views = $this->viewRegistry->getViews();
        $this->assertCount(4, $views);
        $this->assertEquals(
            ['view1' => 'test1', 'override' => 'override'],
            $this->readObjectAttribute($views[0], 'options')
        );
        $this->assertEquals(
            ['view1' => 'test1', 'view1_1' => 'test1_1', 'override' => 'override'],
            $this->readObjectAttribute($views[1], 'options')
        );
        $this->assertEquals(
            [
                'view1' => 'test1',
                'view1_1' => 'test1_1',
                'view1_1_1' => 'test1_1_1',
                'override' => 'overriden-value',
            ],
            $this->readObjectAttribute($views[2], 'options')
        );
        $this->assertEquals(['value' => 'test'], $this->readObjectAttribute($views[3], 'options'));
    }
}
