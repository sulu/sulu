<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CachedFormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

class CachedFormMetadataProviderTest extends TestCase
{
    public function testGetMetadataDelegatesToInner(): void
    {
        $metadata = $this->createMetadata();
        $callCount = 0;
        $inner = $this->createInnerProvider(['page' => ['en' => $metadata]], $callCount);
        $cached = new CachedFormMetadataProvider($inner);

        $result = $cached->getMetadata('page', 'en', []);

        $this->assertSame($metadata, $result);
        $this->assertSame(1, $callCount);
    }

    public function testGetMetadataCachesResult(): void
    {
        $metadata = $this->createMetadata();
        $callCount = 0;
        $inner = $this->createInnerProvider(['page' => ['en' => $metadata]], $callCount);
        $cached = new CachedFormMetadataProvider($inner);

        $result1 = $cached->getMetadata('page', 'en', []);
        $result2 = $cached->getMetadata('page', 'en', []);

        $this->assertSame($metadata, $result1);
        $this->assertSame($metadata, $result2);
        $this->assertSame(1, $callCount);
    }

    public function testGetMetadataWithDifferentKeyProducesSeparateCacheEntries(): void
    {
        $metadata1 = $this->createMetadata();
        $metadata2 = $this->createMetadata();
        $callCount = 0;
        $inner = $this->createInnerProvider([
            'page' => ['en' => $metadata1],
            'article' => ['en' => $metadata2],
        ], $callCount);
        $cached = new CachedFormMetadataProvider($inner);

        $result1 = $cached->getMetadata('page', 'en', []);
        $result2 = $cached->getMetadata('article', 'en', []);

        $this->assertSame($metadata1, $result1);
        $this->assertSame($metadata2, $result2);
        $this->assertSame(2, $callCount);
    }

    public function testGetMetadataWithDifferentLocaleProducesSeparateCacheEntries(): void
    {
        $metadata1 = $this->createMetadata();
        $metadata2 = $this->createMetadata();
        $callCount = 0;
        $inner = $this->createInnerProvider([
            'page' => ['en' => $metadata1, 'de' => $metadata2],
        ], $callCount);
        $cached = new CachedFormMetadataProvider($inner);

        $result1 = $cached->getMetadata('page', 'en', []);
        $result2 = $cached->getMetadata('page', 'de', []);

        $this->assertSame($metadata1, $result1);
        $this->assertSame($metadata2, $result2);
        $this->assertSame(2, $callCount);
    }

    public function testGetMetadataCachesNonCacheableResult(): void
    {
        $metadata = new class() implements MetadataInterface {
            public function isCacheable(): bool
            {
                return false;
            }
        };
        $callCount = 0;
        $inner = $this->createInnerProvider(['page' => ['en' => $metadata]], $callCount);
        $cached = new CachedFormMetadataProvider($inner);

        $cached->getMetadata('page', 'en', []);
        $cached->getMetadata('page', 'en', []);

        $this->assertSame(1, $callCount);
    }

    public function testResetClearsCache(): void
    {
        $metadata = $this->createMetadata();
        $callCount = 0;
        $inner = $this->createInnerProvider(['page' => ['en' => $metadata]], $callCount);
        $cached = new CachedFormMetadataProvider($inner);

        $cached->getMetadata('page', 'en', []);
        $cached->reset();
        $cached->getMetadata('page', 'en', []);

        $this->assertSame(2, $callCount);
    }

    public function testGetMetadataForwardsException(): void
    {
        $callCount = 0;
        $inner = $this->createInnerProvider([], $callCount);
        $cached = new CachedFormMetadataProvider($inner);

        $this->expectException(MetadataNotFoundException::class);

        $cached->getMetadata('nonexistent', 'en', []);
    }

    /**
     * @param array<string, array<string, MetadataInterface>> $metadataMap
     */
    private function createInnerProvider(array $metadataMap, int &$callCount): MetadataProviderInterface
    {
        return new class($metadataMap, $callCount) implements MetadataProviderInterface {
            /**
             * @param array<string, array<string, MetadataInterface>> $metadataMap
             */
            public function __construct(
                private array $metadataMap,
                private int &$callCount,
            ) {
            }

            public function getMetadata(string $key, string $locale, array $metadataOptions = []): MetadataInterface
            {
                ++$this->callCount;

                if (!isset($this->metadataMap[$key][$locale])) {
                    throw new MetadataNotFoundException('form', $key);
                }

                return $this->metadataMap[$key][$locale];
            }
        };
    }

    private function createMetadata(): MetadataInterface
    {
        return new class() implements MetadataInterface {
            public function isCacheable(): bool
            {
                return true;
            }
        };
    }
}
