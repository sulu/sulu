<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\ReferenceStore;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreNotExistsException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePool;

class ReferenceStorePoolTest extends TestCase
{
    public function testGetStores()
    {
        $innerStore = $this->prophesize(ReferenceStoreInterface::class);

        $store = new ReferenceStorePool(['test' => $innerStore->reveal()]);

        $this->assertEquals(['test' => $innerStore->reveal()], $store->getStores());
    }

    public function testGetStore()
    {
        $innerStore = $this->prophesize(ReferenceStoreInterface::class);

        $store = new ReferenceStorePool(['test' => $innerStore->reveal()]);

        $this->assertEquals($innerStore->reveal(), $store->getStore('test'));
    }

    public function testGetStoreNotExisting()
    {
        $this->expectException(ReferenceStoreNotExistsException::class);
        $store = new ReferenceStorePool([]);

        $store->getStore('test');
    }
}
