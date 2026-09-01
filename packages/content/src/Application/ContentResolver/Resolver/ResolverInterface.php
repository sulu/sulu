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
 * Register with the `sulu_content.content_resolver` tag. `type` keys the output and defaults
 * to the class's `#[AsTaggedItem]` index or static `getDefaultTypeName()`, then the decorated
 * service id, then the service id, mirroring Symfony's tagged iterator resolution. `path` sets
 * the output location in the resolved envelope and defaults to
 * `[root][extension][<type>]`. A path ending in `content` also places the view at the sibling
 * `view` key. Higher `priority` runs first and wins on key collisions.
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
}
