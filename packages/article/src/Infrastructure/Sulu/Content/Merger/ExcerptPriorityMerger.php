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

namespace Sulu\Article\Infrastructure\Sulu\Content\Merger;

use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;

class ExcerptPriorityMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof ArticleDimensionContentInterface) {
            return;
        }

        if (!$sourceObject instanceof ArticleDimensionContentInterface) {
            return;
        }

        // The excerpt priority is a localized value; the unlocalized dimension content always keeps the
        // default of 0. Only overwrite when the source carries a value so the merge stays independent of
        // the order in which the dimension contents are merged.
        $excerptPriority = $sourceObject->getExcerptPriority();
        if (0 !== $excerptPriority) {
            $targetObject->setExcerptPriority($excerptPriority);
        }
    }
}
