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
     *     ...
     * }
     */
    public function normalizeContentViewData(
        array $content,
        array $view,
        ContentRichEntityInterface $resource
    ): array;

    /**
     * Runs replaceNestedContentViews for the root envelope and every configured `[root][x][content]`
     * envelope, so those envelopes flatten nested content the same way the root envelope does.
     *
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>,
     *     ...
     * } $contentData
     */
    public function replaceNestedContentViewsAtEnvelopes(array &$contentData): void;

    /**
     * Folds per-item field-level view data sitting at numeric indices into the
     * corresponding `items` entry produced by viewEnhancements.
     *
     * @param array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>, ...} $data
     * @param array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}> $viewEnhancements
     *
     * @return array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>, ...}
     */
    public function mergeFieldViewDataIntoItems(array $data, array $viewEnhancements): array;

    /**
     * Recursively maps properties in the content data.
     *
     * @param array{
     *      resource: object,
     *      content: array<string, mixed>,
     *      view: array<string, mixed>,
     *      extension: array<string, array<string, mixed>>,
     *      ...
     *  } $data
     * @param array<string, string> $properties
     * @param list<int|string> $path
     */
    public function recursivelyMapProperties(
        array &$data,
        array $properties,
        array $path = [],
        int $depth = 0,
        bool $isRoot = true
    ): void;
}
