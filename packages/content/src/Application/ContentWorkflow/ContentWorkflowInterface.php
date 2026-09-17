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

namespace Sulu\Content\Application\ContentWorkflow;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

interface ContentWorkflowInterface
{
    public const CONTENT_RICH_ENTITY_CONTEXT_KEY = 'contentRichEntity';
    public const DIMENSION_CONTENT_COLLECTION_CONTEXT_KEY = 'dimensionContentCollection';
    public const DIMENSION_ATTRIBUTES_CONTEXT_KEY = 'dimensionAttributes';

    /**
     * A guard listener that refuses a transition for its own reason blocks it with this code and
     * puts the exception to report under {@see BLOCKER_EXCEPTION_PARAMETER}, so the caller sees that
     * reason instead of a generic "transition not enabled".
     */
    public const BLOCKER_CODE_EXCEPTION = 'sulu_content.blocked_by_exception';

    public const BLOCKER_EXCEPTION_PARAMETER = 'exception';

    /**
     * @template T of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<T> $contentRichEntity
     * @param mixed[] $dimensionAttributes
     *
     * @return T
     */
    public function apply(
        ContentRichEntityInterface $contentRichEntity,
        array $dimensionAttributes,
        string $transitionName
    ): DimensionContentInterface;
}
