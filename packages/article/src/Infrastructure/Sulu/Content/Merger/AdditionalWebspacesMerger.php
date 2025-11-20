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

class AdditionalWebspacesMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof ArticleDimensionContentInterface) {
            return;
        }

        if (!$sourceObject instanceof ArticleDimensionContentInterface) {
            return;
        }

        // Merge customizeWebspaceSettings flag
        if ($sourceObject->getCustomizeWebspaceSettings()) {
            $targetObject->setCustomizeWebspaceSettings($sourceObject->getCustomizeWebspaceSettings());
        }

        // Merge additionalWebspaces array
        $additionalWebspaces = $sourceObject->getAdditionalWebspaces();
        $targetObject->setAdditionalWebspaces($additionalWebspaces);
    }
}
