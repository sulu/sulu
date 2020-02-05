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
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderPool;

class PreviewObjectProviderPoolTest extends TestCase
{
    /**
     * @var PreviewObjectProviderPoolInterface
     */
    private $objectProviderPool;

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

        $this->objectProviderPool = new PreviewObjectProviderPool($providers);
    }

    public function testGetObjectProviders(): void
    {
        $objectProviders = $this->objectProviderPool->getObjectProviders();

        $this->assertCount(1, $objectProviders);
        $this->assertArrayHasKey($this->providerKey, $objectProviders);
        $this->assertArrayNotHasKey('wrong-key', $objectProviders);
    }

    public function testGetObjectProvider(): void
    {
        $this->assertEquals($this->provider->reveal(), $this->objectProviderPool->getObjectProvider($this->providerKey));

        $this->expectException(ProviderNotFoundException::class);
        $this->objectProviderPool->getObjectProvider('wrong-key');
    }

    public function testHasObjectProvider(): void
    {
        $this->assertTrue($this->objectProviderPool->hasObjectProvider($this->providerKey));
        $this->assertFalse($this->objectProviderPool->hasObjectProvider('wrong-key'));
    }
}
