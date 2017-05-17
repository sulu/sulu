<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\ReferenceStore;

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ContentBundle\ReferenceStore\ChainReferenceStore;
use Sulu\Bundle\ContentBundle\ReferenceStore\ReferenceStoreInterface;

class ChainReferenceStoreTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $innerStore = $this->prophesize(ReferenceStoreInterface::class);

        $store = new ChainReferenceStore(['test' => $innerStore->reveal()]);

        $store->add('test-1');

        $innerStore->add('1')->shouldHaveBeenCalled();
    }

    public function testAddUuid()
    {
        $innerStore = $this->prophesize(ReferenceStoreInterface::class);

        $store = new ChainReferenceStore(['test' => $innerStore->reveal()]);

        $store->add('test-123-123-123');

        $innerStore->add('123-123-123')->shouldHaveBeenCalled();
    }

    /**
     * @expectedException \Sulu\Bundle\ContentBundle\ReferenceStore\ReferenceStoreNotExistsException
     */
    public function testAddNotExistingStore()
    {
        $store = new ChainReferenceStore([]);

        $store->add('test-1');
    }

    /**
     * @expectedException \Sulu\Bundle\ContentBundle\ReferenceStore\ReferenceStoreInvalidIdException
     */
    public function testAddInvalidId()
    {
        $innerStore = $this->prophesize(ReferenceStoreInterface::class);

        $store = new ChainReferenceStore(['test' => $innerStore->reveal()]);

        $store->add('test#1');
    }

    public function testGetAll()
    {
        $innerStore = $this->prophesize(ReferenceStoreInterface::class);
        $innerStore->getAll()->willReturn([1, 2, 3]);

        $store = new ChainReferenceStore(['test1' => $innerStore->reveal(), 'test2' => $innerStore->reveal()]);

        $this->assertEquals(
            [
                'test1-1',
                'test1-2',
                'test1-3',
                'test2-1',
                'test2-2',
                'test2-3',
            ],
            $store->getAll()
        );
    }

    public function testGetAllUuid()
    {
        $uuid = Uuid::uuid4()->toString();

        $innerStore = $this->prophesize(ReferenceStoreInterface::class);
        $innerStore->getAll()->willReturn([$uuid]);

        $store = new ChainReferenceStore(['test' => $innerStore->reveal()]);

        $this->assertEquals([$uuid], $store->getAll());
    }
}
