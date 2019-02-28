<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Exception\MetadataProviderNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;

class MetadataProviderRegistryTest extends TestCase
{
    /**
     * @var MetadataProviderRegistry;
     */
    private $metadataProviderRegistry;

    public function setUp()
    {
        $this->metadataProviderRegistry = new MetadataProviderRegistry();
    }

    public function testGetMetadataProvider()
    {
        $metadataProvider1 = $this->prophesize(MetadataProviderInterface::class);
        $metadataProvider2 = $this->prophesize(MetadataProviderInterface::class);

        $this->metadataProviderRegistry->addMetadataProvider('test1', $metadataProvider1->reveal());
        $this->metadataProviderRegistry->addMetadataProvider('test2', $metadataProvider2->reveal());

        $this->assertSame(
            $metadataProvider1->reveal(),
            $this->metadataProviderRegistry->getMetadataProvider('test1')
        );
        $this->assertSame(
            $metadataProvider2->reveal(),
            $this->metadataProviderRegistry->getMetadataProvider('test2')
        );
    }

    public function testGetNotExistingMetadataProvider()
    {
        $this->expectException(MetadataProviderNotFoundException::class);
        $this->metadataProviderRegistry->getMetadataProvider('test1');
    }
}
