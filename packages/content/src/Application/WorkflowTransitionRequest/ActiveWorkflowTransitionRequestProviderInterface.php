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

namespace Sulu\Content\Application\WorkflowTransitionRequest;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * Owns the one filter that answers "is this content in review": at most one request is active per
 * resource and locale, and every caller has to ask the same question the same way.
 *
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface ActiveWorkflowTransitionRequestProviderInterface
{
    public function find(string $resourceKey, string $resourceId, string $locale): ?WorkflowTransitionRequest;

    /**
     * @template T of \Sulu\Content\Domain\Model\ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     */
    public function findForContent(DimensionContentInterface $dimensionContent): ?WorkflowTransitionRequest;
}
