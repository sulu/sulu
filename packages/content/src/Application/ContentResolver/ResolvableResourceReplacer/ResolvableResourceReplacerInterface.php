<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer;

/**
 * @internal This interface is intended for internal use only within the package/library.
 * Modifying or depending on this interface may result in unexpected behavior and is not supported.
 */
interface ResolvableResourceReplacerInterface
{
    /**
     * @param array<string, mixed> $content
     * @param array<string, array<string|int, array<string, mixed>>> $resolvedResources
     *
     * @return array<string, mixed>
     */
    public function replaceResolvableResourcesWithResolvedValues(
        array $content,
        array $resolvedResources,
        int $depth,
        int $maxDepth
    ): array;
}
