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

namespace Sulu\Content\Application\ContentResolver\ContentViewResolver;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal This interface is intended for internal use only within the package/library.
 * Modifying or depending on this interface may result in unexpected behavior and is not supported.
 */
interface ContentViewResolverInterface
{
    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     * @param array<string, mixed>|null $properties
     *
     * @return array<string|int, ContentView>
     */
    public function getContentViews(DimensionContentInterface $dimensionContent, ?array $properties = null): array;

    /**
     * @param ContentView[] $contentViews
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> &$priorityQueue Reference to the priority queue
     *
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>>,
     * }
     */
    public function resolveContentViews(array $contentViews, int $depth, array &$priorityQueue = []): array;

    /**
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> &$priorityQueue Reference to the priority queue
     *
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>>
     * }
     */
    public function resolveContentView(ContentView $contentView, string $name, int $depth, array &$priorityQueue = []): array;
}
