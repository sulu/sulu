<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview\Object;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;

class PreviewObjectProviderRegistryTest extends TestCase
{
    use ProphecyTrait;

    public function testGetPreviewObjectProviders(): void
    {
        $provider = $this->prophesize(PreviewDefaultsProviderInterface::class);
        $providerKey = 'test-provider';
        $providers = [$providerKey => $provider->reveal()];
        $previewObjectProviderRegistry = new PreviewObjectProviderRegistry($providers);

        $previewObjectProviders = $previewObjectProviderRegistry->getPreviewObjectProviders();

        $this->assertCount(1, $previewObjectProviders);
        $this->assertArrayHasKey($providerKey, $previewObjectProviders);
        $this->assertArrayNotHasKey('wrong-key', $previewObjectProviders);
    }

    public function testGetPreviewObjectProvider(): void
    {
        $provider = $this->prophesize(PreviewDefaultsProviderInterface::class);
        $providerKey = 'test-provider';
        $providers = [$providerKey => $provider->reveal()];
        $previewObjectProviderRegistry = new PreviewObjectProviderRegistry($providers);

        $this->assertEquals(
            $provider->reveal(),
            $previewObjectProviderRegistry->getPreviewObjectProvider($providerKey)
        );
    }

    public function testGetNonExistingPreviewObjectProvider(): void
    {
        $previewObjectProviderRegistry = new PreviewObjectProviderRegistry([]);

        $this->expectException(ProviderNotFoundException::class);
        $previewObjectProviderRegistry->getPreviewObjectProvider('wrong-key');
    }

    public function testHasPreviewObjectProvider(): void
    {
        $provider = $this->prophesize(PreviewDefaultsProviderInterface::class);
        $providerKey = 'test-provider';
        $providers = [$providerKey => $provider->reveal()];
        $previewObjectProviderRegistry = new PreviewObjectProviderRegistry($providers);

        $this->assertTrue($previewObjectProviderRegistry->hasPreviewObjectProvider($providerKey));
        $this->assertFalse($previewObjectProviderRegistry->hasPreviewObjectProvider('wrong-key'));
    }
}
