<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Metadata;

use JMS\Serializer\Metadata\PropertyMetadata as BasePropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\PropertyMetadata;

class PropertyMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $metadata = new PropertyMetadata(PropertyMetadataTestTestClass::class, 'test');
        $metadata->addMetadata('Test', new BasePropertyMetadata(PropertyMetadataTestTestClass::class, 'test'));

        $this->assertEquals($metadata, unserialize(serialize($metadata)));
    }
}

class PropertyMetadataTestTestClass
{
    public $test;
}
