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

use Sulu\Content\Application\ContentResolver\Value\ContentView;

/**
 * @internal This interface is intended for internal use only within the package/library.
 * Modifying or depending on this interface may result in unexpected behavior and is not supported.
 */
interface ResolvableResourceReplacerInterface
{
    /**
     * @param array<int|string, mixed> $content
     * @param array<string, array<string|int, array<string, array{resolved: mixed, contentViewEnhancement: ContentView}>>> $resolvedResources
     *
     * @return array{
     *     content: array<int|string, mixed>,
     *     viewEnhancements: array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}>,
     * }
     */
    public function replaceResolvableResourcesWithResolvedValues(
        array $content,
        array $resolvedResources,
        int $depth,
        int $maxDepth
    ): array;
}
