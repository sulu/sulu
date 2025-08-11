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

namespace Sulu\Content\Application\ContentResolver\DataNormalizer;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal This interface is intended for internal use only within the package/library.
 * Modifying or depending on this interface may result in unexpected behavior and is not supported.
 */
interface ContentViewDataNormalizerInterface
{
    /**
     * @template T of DimensionContentInterface
     *
     * @param array<string, mixed> $content
     * @param array<string, mixed> $view
     * @param ContentRichEntityInterface<T> $resource
     *
     * @return array{
     *     resource: ContentRichEntityInterface<T>,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>,
     * }
     */
    public function normalizeContentViewData(
        array $content,
        array $view,
        ContentRichEntityInterface $resource
    ): array;

    /**
     * Replaces nested ContentViews in the formatted content data.
     *
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>
     * } $contentData
     */
    public function replaceNestedContentViews(array &$contentData, string $path = '[content]'): void;

    /**
     * Recursively maps properties in the content data.
     *
     * @param array{
     *      resource: object,
     *      content: array<string, mixed>,
     *      view: array<string, mixed>,
     *      extension: array<string, array<string, mixed>>,
     *  } $data
     * @param array<string, string> $properties
     */
    public function recursivelyMapProperties(
        array &$data,
        array $properties,
        string $path = '',
        int $depth = 0,
        bool $isRoot = true
    ): void;
}
