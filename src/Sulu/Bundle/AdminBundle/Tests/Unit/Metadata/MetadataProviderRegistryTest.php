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
use Psr\Container\ContainerExceptionInterface;
use Sulu\Bundle\AdminBundle\Exception\MetadataProviderNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Symfony\Component\DependencyInjection\Container;

class MetadataProviderRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @throws MetadataProviderNotFoundException
     * @throws ContainerExceptionInterface
     */
    public function testGetMetadataProvider(): void
    {
        $metadataProvider1 = $this->prophesize(MetadataProviderInterface::class)->reveal();
        $metadataProvider2 = $this->prophesize(MetadataProviderInterface::class)->reveal();

        $container = new Container();
        $container->set('form', $metadataProvider1);
        $container->set('list', $metadataProvider2);

        $metadataProviderRegistry = new MetadataProviderRegistry($container);

        $this->assertSame($metadataProvider1, $metadataProvider1->getMetadataProvider('form'));
        $this->assertSame($metadataProvider2, $metadataProviderRegistry->getMetadataProvider('list'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testGetNotExistingMetadataProvider(): void
    {
        $metadataProviderRegistry = new MetadataProviderRegistry();

        $this->expectException(MetadataProviderNotFoundException::class);

        $metadataProviderRegistry->getMetadataProvider('foobar');
    }
}
