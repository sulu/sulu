<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Content\Application\ResourceLoader\Loader\CachedResourceLoader;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;

class CachedResourceLoaderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ResourceLoaderInterface>
     */
    private $decoratedResourceLoader;

    private CachedResourceLoader $cachedResourceLoader;

    protected function setUp(): void
    {
        $this->decoratedResourceLoader = $this->prophesize(ResourceLoaderInterface::class);
        $this->cachedResourceLoader = new CachedResourceLoader(
            $this->decoratedResourceLoader->reveal()
        );
    }

    public function testLoad(): void
    {
        $ids = ['id1', 'id2'];
        $locale = 'en';
        $params = ['param1' => 'value1'];
        $expectedResult = ['result1', 'result2'];

        $this->decoratedResourceLoader->load($ids, $locale, $params)
            ->shouldBeCalledTimes(1)
            ->willReturn($expectedResult);

        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame($expectedResult, $result);

        // Second call should use cache
        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame($expectedResult, $result);
    }

    public function testLoadWithDifferentParameters(): void
    {
        $ids1 = ['id1', 'id2'];
        $ids2 = ['id3', 'id4'];
        $locale = 'en';
        $params = ['param1' => 'value1'];
        $result1 = ['result1', 'result2'];
        $result2 = ['result3', 'result4'];

        $this->decoratedResourceLoader->load($ids1, $locale, $params)
            ->shouldBeCalledTimes(1)
            ->willReturn($result1);

        $this->decoratedResourceLoader->load($ids2, $locale, $params)
            ->shouldBeCalledTimes(1)
            ->willReturn($result2);

        $this->assertSame($result1, $this->cachedResourceLoader->load($ids1, $locale, $params));
        $this->assertSame($result2, $this->cachedResourceLoader->load($ids2, $locale, $params));

        // Call again to verify cache is used
        $this->assertSame($result1, $this->cachedResourceLoader->load($ids1, $locale, $params));
        $this->assertSame($result2, $this->cachedResourceLoader->load($ids2, $locale, $params));
    }

    public function testReset(): void
    {
        $ids = ['id1', 'id2'];
        $locale = 'en';
        $params = ['param1' => 'value1'];
        $expectedResult = ['result1', 'result2'];

        $this->decoratedResourceLoader->load($ids, $locale, $params)
            ->shouldBeCalledTimes(2)
            ->willReturn($expectedResult);

        // First call, should call decorated loader
        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame($expectedResult, $result);

        // Reset cache
        $this->cachedResourceLoader->reset();

        // Should call decorated loader again after reset
        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame($expectedResult, $result);
    }

    public function testGetKey(): void
    {
        $this->expectException(\LogicException::class);
        CachedResourceLoader::getKey();
    }
}
