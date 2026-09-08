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

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Resolves one aspect of a DimensionContent (template data, seo, settings) into a ContentView.
 *
 * Implementations are tagged `sulu_content.content_resolver` by autoconfiguration. `getType()`
 * keys the output and `getOutputPath()` places it in the resolved content view data. Resolvers
 * may share a path: higher tag `priority` runs first and its values win on a key collision.
 *
 * Resolvers run for every resolved entity at every depth. When `$properties` is set, filter
 * by your own prefix and return null or a subset instead of loading unrequested data.
 */
interface ResolverInterface
{
    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     * @param array<string, string>|null $properties
     */
    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView;

    /**
     * Keys this resolver's output, e.g. `seo`.
     */
    public function getType(): string;

    /**
     * Where the output lands in the resolved content view data, as a bracket path relative to
     * the root, e.g. `[product]`. A path ending in `content` also writes the view to the
     * sibling `view` key.
     *
     * `null` is the default location, `[extension][<type>]`. The empty string is the opposite:
     * it merges the output into the root itself.
     */
    public function getOutputPath(): ?string;
}
