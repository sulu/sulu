<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal this class should not be extended or initialized by any application outside of sulu
 */
final class CachedFormMetadataProvider implements MetadataProviderInterface, ResetInterface
{
    /**
     * @var array<string, MetadataInterface>
     */
    private array $cache = [];

    public function __construct(
        private MetadataProviderInterface $inner,
    ) {
    }

    public function getMetadata(...$args): MetadataInterface // @phpstan-ignore missingType.parameter
    {
        $cacheKey = $this->buildCacheKey($args);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $metadata = $this->inner->getMetadata(...$args); // @phpstan-ignore argument.type

        $this->cache[$cacheKey] = $metadata;

        return $metadata;
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    /**
     * @param array<int|string, mixed> $input
     */
    private function buildCacheKey(array $input): string
    {
        return \hash('xxh128', $this->normalizeValue($input));
    }

    private function normalizeValue(mixed $value): string
    {
        if (\is_array($value)) {
            $parts = [];
            foreach ($value as $k => $v) {
                $parts[] = $k . ':' . $this->normalizeValue($v);
            }

            return \implode(',', $parts);
        }

        if (\is_object($value)) {
            return \spl_object_hash($value);
        }

        \assert(\is_scalar($value) || null === $value, 'Expected a scalar value here got: ' . \get_debug_type($value));

        return (string) $value;
    }
}
