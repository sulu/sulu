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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Exception\MetadataProviderNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;

class MetadataProviderRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var MetadataProviderRegistry
     */
    private $metadataProviderRegistry;

    public function setUp(): void
    {
        $this->metadataProviderRegistry = new MetadataProviderRegistry();
    }

    public function testGetMetadataProvider(): void
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

    public function testGetNotExistingMetadataProvider(): void
    {
        $this->expectException(MetadataProviderNotFoundException::class);
        $this->metadataProviderRegistry->getMetadataProvider('test1');
    }
}
