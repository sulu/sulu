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
 * Enhancers can modify dimension content or replacing it with another dimension content before the
 * content is resolved into its final form.
 */
interface DimensionContentEnhancerInterface
{
    /**
     * Enhances the given dimension content.
     *
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent The dimension content to enhance
     *
     * @return T The enhanced dimension content (may be a different instance)
     */
    public function enhance(DimensionContentInterface $dimensionContent): DimensionContentInterface;
}
