<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Metadata\Provider;

use Metadata\MetadataFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\MetadataProvider;

class MetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMetadataForClass()
    {
        $factory = $this->prophesize(MetadataFactoryInterface::class);
        $factory->getMetadataForClass(self::class)->willReturn(true);

        $provider = new MetadataProvider($factory->reveal());

        $this->assertTrue($provider->getMetadataForClass(self::class));
    }
}
