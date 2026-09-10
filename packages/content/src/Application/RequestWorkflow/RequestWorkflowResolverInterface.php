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

namespace Sulu\Content\Application\RequestWorkflow;

use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface RequestWorkflowResolverInterface
{
    /**
     * Resolve the request workflow that applies to the given dimension content. Returns `null` when
     * publishes are not subject to a review request, see {@see RequestWorkflowResolver} for the rules.
     *
     * @template T of \Sulu\Content\Domain\Model\ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    public function resolveForContent(DimensionContentInterface $dimensionContent): ?RequestWorkflow;
}
