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

interface ContentEnhancerInterface
{
    /**
     * Enhances the given dimension content by running all registered enhancers.
     *
     * @template T of \Sulu\Content\Domain\Model\ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     *
     * @return DimensionContentInterface<T>
     */
    public function enhance(DimensionContentInterface $dimensionContent): DimensionContentInterface;
}
