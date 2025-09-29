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

namespace Sulu\Article\Application\Content\Merger;

use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;

class AdditionalWebspacesMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof AdditionalWebspacesInterface) {
            return;
        }

        if (!$sourceObject instanceof AdditionalWebspacesInterface) {
            return;
        }

        // Merge customizeWebspaceSettings flag
        if ($sourceObject->getCustomizeWebspaceSettings()) {
            $targetObject->setCustomizeWebspaceSettings($sourceObject->getCustomizeWebspaceSettings());
        }

        // Merge additionalWebspaces array
        $additionalWebspaces = $sourceObject->getAdditionalWebspaces();
        if (null !== $additionalWebspaces) {
            $targetObject->setAdditionalWebspaces($additionalWebspaces);
        }
    }
}
