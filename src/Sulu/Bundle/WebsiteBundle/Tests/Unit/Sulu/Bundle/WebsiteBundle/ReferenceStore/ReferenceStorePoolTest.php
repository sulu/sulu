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

use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePool;

class ReferenceStorePoolTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @expectedException \Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreNotExistsException
     */
    public function testGetStoreNotExisting()
    {
        $store = new ReferenceStorePool([]);

        $store->getStore('test');
    }
}
