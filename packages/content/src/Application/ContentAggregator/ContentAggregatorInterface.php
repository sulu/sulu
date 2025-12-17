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

namespace Sulu\Content\Application\ContentAggregator;

use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ContentAggregatorInterface
{
    /**
     * IMPORTANT: For optimal performance, dimension contents MUST be eager-loaded before calling this method.
     *
     * All data is retrieved from $contentRichEntity->getDimensionContents() and filtered in-memory.
     * In debug mode: Throws RuntimeException if dimension contents are not initialized (enforces best practice).
     * In production mode: Allows lazy loading if needed (graceful degradation, but with significant performance penalty).
     *
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     *
     * @throws \RuntimeException If dimension contents are not initialized (debug mode only)
     * @throws ContentNotFoundException If no dimension contents match the attributes
     */
    public function aggregate(ContentRichEntityInterface $contentRichEntity, array $dimensionAttributes): DimensionContentInterface;
}
