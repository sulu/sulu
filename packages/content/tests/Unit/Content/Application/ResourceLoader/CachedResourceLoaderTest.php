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
use Sulu\Content\Application\ResourceLoader\Loader\CachedResourceLoader;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;

class CachedResourceLoaderTest extends TestCase
{
    private ResourceLoaderInterface $decoratedResourceLoader;
    private CachedResourceLoader $cachedResourceLoader;

    protected function setUp(): void
    {
        $this->decoratedResourceLoader = new class() implements ResourceLoaderInterface {
            /** @var array<string, mixed> */
            public static array $values = [];

            public function load(array $ids, ?string $locale, array $params = []): array
            {
                $models = [];
                foreach ($ids as $id) {
                    if (!isset(self::$values[$id])) {
                        throw new \LogicException(\sprintf('The CachedResourceLoader should never call the same "id" multiple times. Id "%s" requested again', $id));
                    }

                    $models[$id] = self::$values[$id];
                    unset(self::$values[$id]); // unset to test not called again with same id
                }

                return $models;
            }

            public static function getKey(): string
            {
                return 'test-loader';
            }
        };

        $this->cachedResourceLoader = new CachedResourceLoader($this->decoratedResourceLoader);
    }

    public function testLoad(): void
    {
        $ids = ['id1', 'id2'];
        $locale = 'en';
        $params = ['param1' => 'value1'];
        $expectedResult = ['id1' => 'result1', 'id2' => 'result2'];

        // Set the values that should be returned
        $this->setDecoratedResourceLoaderValues([
            'id1' => 'result1',
            'id2' => 'result2',
        ]);

        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame($expectedResult, $result);

        // Second call should use cache, not accessing the loader again
        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame($expectedResult, $result);
    }

    public function testLoadWithDifferentParameters(): void
    {
        $ids1 = ['id1', 'id2'];
        $ids2 = ['id3', 'id4'];
        $locale = 'en';
        $params = ['param1' => 'value1'];

        // Set the values for first call
        $this->setDecoratedResourceLoaderValues([
            'id1' => 'result1',
            'id2' => 'result2',
        ]);

        $result1 = $this->cachedResourceLoader->load($ids1, $locale, $params);
        $this->assertSame(['id1' => 'result1', 'id2' => 'result2'], $result1);

        // Set values for second call with different IDs
        $this->setDecoratedResourceLoaderValues([
            'id3' => 'result3',
            'id4' => 'result4',
        ]);

        $result2 = $this->cachedResourceLoader->load($ids2, $locale, $params);
        $this->assertSame(['id3' => 'result3', 'id4' => 'result4'], $result2);

        // Call again to verify cache is used (no need to reset values)
        $resultFromCache1 = $this->cachedResourceLoader->load($ids1, $locale, $params);
        $resultFromCache2 = $this->cachedResourceLoader->load($ids2, $locale, $params);

        $this->assertSame(['id1' => 'result1', 'id2' => 'result2'], $resultFromCache1);
        $this->assertSame(['id3' => 'result3', 'id4' => 'result4'], $resultFromCache2);
    }

    public function testReset(): void
    {
        $ids = ['id1', 'id2'];
        $locale = 'en';
        $params = ['param1' => 'value1'];

        // Set values for first load
        $this->setDecoratedResourceLoaderValues([
            'id1' => 'result1',
            'id2' => 'result2',
        ]);

        // First call, should consume values
        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame(['id1' => 'result1', 'id2' => 'result2'], $result);

        // Reset cache
        $this->cachedResourceLoader->reset();

        // Need to set values again since they were consumed and we're testing a fresh load
        $this->setDecoratedResourceLoaderValues([
            'id1' => 'result1',
            'id2' => 'result2',
        ]);

        // Should call decorated loader again after reset
        $result = $this->cachedResourceLoader->load($ids, $locale, $params);
        $this->assertSame(['id1' => 'result1', 'id2' => 'result2'], $result);
    }

    public function testPartialCacheHit(): void
    {
        $locale = 'en';
        $params = ['param1' => 'value1'];

        // First load - set values for id1 and id2
        $this->setDecoratedResourceLoaderValues([
            'id1' => 'result1',
            'id2' => 'result2',
        ]);

        $this->cachedResourceLoader->load(['id1', 'id2'], $locale, $params);

        // Second load with one new ID and one already cached
        // Only set the value for the new ID
        $this->setDecoratedResourceLoaderValues([
            'id3' => 'result3',
        ]);

        $result = $this->cachedResourceLoader->load(['id1', 'id3'], $locale, $params);
        $this->assertSame(['id1' => 'result1', 'id3' => 'result3'], $result);
    }

    public function testGetKey(): void
    {
        $this->expectException(\LogicException::class);
        CachedResourceLoader::getKey();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setDecoratedResourceLoaderValues(array $values): void
    {
        $this->decoratedResourceLoader::$values = $values; // @phpstan-ignore staticProperty.notFound
    }
}
