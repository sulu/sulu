<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\ResourceMetadata;

use Sulu\Bundle\AdminBundle\Exception\ResourceNotFoundException;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataPool;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataProviderInterface;

class ResourceMetadataPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceMetadataPool
     */
    protected $resourceMetadataPool;

    /**
     * @var ResourceMetadataProviderInterface
     */
    protected $provider1;

    /**
     * @var ResourceMetadataProviderInterface
     */
    protected $provider2;

    public function setUp()
    {
        $this->resourceMetadataPool = new ResourceMetadataPool();
        $this->provider1 = $this->prophesize(ResourceMetadataProviderInterface::class);
        $this->provider2 = $this->prophesize(ResourceMetadataProviderInterface::class);

        $this->resourceMetadataPool->addResourceMetadataProvider($this->provider1->reveal());
        $this->resourceMetadataPool->addResourceMetadataProvider($this->provider2->reveal());
    }

    public function testGetProviders()
    {
        $this->assertEquals(2, count($this->resourceMetadataPool->getProviders()));
        $this->assertSame($this->provider1->reveal(), $this->resourceMetadataPool->getProviders()[0]);
        $this->assertSame($this->provider2->reveal(), $this->resourceMetadataPool->getProviders()[1]);
    }

    public function testGetResourceMetadata()
    {
        $resourceMetadata = $this->prophesize(ResourceMetadataInterface::class)->reveal();

        $this->provider1->getResourceMetadata('resource_key_test', 'de')->willReturn(null);
        $this->provider2->getResourceMetadata('resource_key_test', 'de')->willReturn($resourceMetadata);

        $this->assertEquals(
            $resourceMetadata,
            $this->resourceMetadataPool->getResourceMetadata('resource_key_test', 'de')
        );
    }

    public function testGetNotExistingResourceMetadata()
    {
        $this->setExpectedException(ResourceNotFoundException::class);

        $this->provider1->getResourceMetadata('resource_key_not_existing', 'de')->willReturn(null);
        $this->provider2->getResourceMetadata('resource_key_not_existing', 'de')->willReturn(null);

        $this->resourceMetadataPool->getResourceMetadata('resource_key_not_existing', 'de');
    }
}
