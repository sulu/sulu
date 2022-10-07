<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\ReferenceStore;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;

class ReferenceStoreTest extends TestCase
{
    public function testAdd(): void
    {
        $store = new ReferenceStore();

        $store->add('123-123-123');

        $this->assertEquals(['123-123-123'], $store->getAll());
    }

    public function testAddSame(): void
    {
        $store = new ReferenceStore();

        $store->add('123-123-123');
        $store->add('123-123-123');

        $this->assertEquals(['123-123-123'], $store->getAll());
    }

    public function testAddDifferent(): void
    {
        $store = new ReferenceStore();

        $store->add('123-123-123');
        $store->add('321-321-321');

        $this->assertEquals(['123-123-123', '321-321-321'], $store->getAll());
    }

    public function testGetAll(): void
    {
        $store = new ReferenceStore();

        $this->assertEquals([], $store->getAll());
    }
}
