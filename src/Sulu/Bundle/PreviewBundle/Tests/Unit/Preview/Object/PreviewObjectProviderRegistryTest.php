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
use Sulu\Bundle\PreviewBundle\Preview\Exception\ProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;

class PreviewObjectProviderRegistryTest extends TestCase
{
    /**
     * @var PreviewObjectProviderRegistryInterface
     */
    private $objectProviderRegistry;

    /**
     * @var PreviewObjectProviderInterface
     */
    private $provider;

    /**
     * @var string
     */
    private $providerKey = 'test-provider';

    public function setUp(): void
    {
        $this->provider = $this->prophesize(PreviewObjectProviderInterface::class);

        $providers = [$this->providerKey => $this->provider->reveal()];

        $this->objectProviderRegistry = new PreviewObjectProviderRegistry($providers);
    }

    public function testGetPreviewObjectProviders(): void
    {
        $objectProviders = $this->objectProviderRegistry->getPreviewObjectProviders();

        $this->assertCount(1, $objectProviders);
        $this->assertArrayHasKey($this->providerKey, $objectProviders);
        $this->assertArrayNotHasKey('wrong-key', $objectProviders);
    }

    public function testGetPreviewObjectProvider(): void
    {
        $this->assertEquals($this->provider->reveal(), $this->objectProviderRegistry->getPreviewObjectProvider($this->providerKey));

        $this->expectException(ProviderNotFoundException::class);
        $this->objectProviderRegistry->getPreviewObjectProvider('wrong-key');
    }

    public function testHasPreviewObjectProvider(): void
    {
        $this->assertTrue($this->objectProviderRegistry->hasPreviewObjectProvider($this->providerKey));
        $this->assertFalse($this->objectProviderRegistry->hasPreviewObjectProvider('wrong-key'));
    }
}
