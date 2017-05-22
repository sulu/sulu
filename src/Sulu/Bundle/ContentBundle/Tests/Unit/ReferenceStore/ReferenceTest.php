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
use Sulu\Bundle\ContentBundle\ReferenceStore\Reference;

class ReferenceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetter()
    {
        $reference = new Reference('test', 1);

        $this->assertEquals('test', $reference->getAlias());
        $this->assertEquals(1, $reference->getId());
    }

    public function testIsUuid()
    {
        $reference = new Reference('test', 1);
        $this->assertFalse($reference->isUuid());

        $reference = new Reference('test', Uuid::uuid4()->toString());
        $this->assertTrue($reference->isUuid());
    }

    public function testToString()
    {
        $reference = new Reference('test', 1);
        $this->assertEquals('test-1', $reference->__toString());
    }

    public function testToStringUuid()
    {
        $uuid = Uuid::uuid4()->toString();

        $reference = new Reference('test', $uuid);
        $this->assertEquals($uuid, $reference->__toString());
    }
}
