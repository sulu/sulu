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

use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ContentAggregationEnhancerInterface
{
    /**
     * Enhancers should check instanceof to determine if they apply to the given dimension content.
     * If not applicable, return the dimension content unchanged.
     *
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     * @param array<string, mixed> $dimensionAttributes
     *
     * @return T The enhanced dimension content (may be a different instance)
     */
    public function enhance(
        DimensionContentInterface $dimensionContent,
        array $dimensionAttributes
    ): DimensionContentInterface;
}
