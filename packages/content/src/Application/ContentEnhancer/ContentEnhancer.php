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

namespace Sulu\Content\Application\ContentEnhancer;

use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Aggregates and executes all registered dimension content enhancers.
 *
 * This service runs all registered enhancers sequentially to transform
 * dimension content before it is used in the application.
 */
readonly class ContentEnhancer implements ContentEnhancerInterface
{
    /**
     * @param iterable<DimensionContentEnhancerInterface> $enhancers
     */
    public function __construct(
        private iterable $enhancers,
    ) {
    }

    public function enhance(DimensionContentInterface $dimensionContent): DimensionContentInterface
    {
        foreach ($this->enhancers as $enhancer) {
            $dimensionContent = $enhancer->enhance($dimensionContent);
        }

        return $dimensionContent;
    }
}
