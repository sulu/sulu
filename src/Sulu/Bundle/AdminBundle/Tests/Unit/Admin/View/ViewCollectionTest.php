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
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;

class ViewCollectionTest extends TestCase
{
    public function testGet(): void
    {
        $viewBuilder = new ViewBuilder('sulu_test', '/test', 'test');

        $viewCollection = new ViewCollection();
        $viewCollection->add($viewBuilder);

        $this->assertEquals($viewBuilder, $viewCollection->get('sulu_test'));
    }

    public function testHas(): void
    {
        $viewBuilder = new ViewBuilder('sulu_test', '/test', 'test');

        $viewCollection = new ViewCollection();
        $viewCollection->add($viewBuilder);

        $this->assertTrue($viewCollection->has('sulu_test'));
    }

    public function testHasNotExisting(): void
    {
        $viewCollection = new ViewCollection();

        $this->assertFalse($viewCollection->has('sulu_test'));
    }

    public function testAll(): void
    {
        $viewBuilder1 = new ViewBuilder('sulu_test_1', '/test', 'test');
        $viewBuilder2 = new ViewBuilder('sulu_test_2', '/test', 'test');

        $viewCollection = new ViewCollection();
        $viewCollection->add($viewBuilder1);
        $viewCollection->add($viewBuilder2);

        $views = $viewCollection->all();

        $this->assertContains($viewBuilder1, $views);
        $this->assertContains($viewBuilder2, $views);
    }

    public function testGetNotExistingView(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $viewCollection = new ViewCollection();
        $viewCollection->get('not-existing');
    }

    public function testRemove(): void
    {
        $viewBuilder = new ViewBuilder('sulu_test', '/test', 'test');

        $viewCollection = new ViewCollection();
        $viewCollection->add($viewBuilder);

        $this->assertTrue($viewCollection->has('sulu_test'));
        $viewCollection->remove($viewBuilder->getName());
        $this->assertFalse($viewCollection->has('sulu_test'));
    }

    public function testRemoveHasNotExisting(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $viewCollection = new ViewCollection();
        $viewCollection->remove('not-existing');
    }
}
