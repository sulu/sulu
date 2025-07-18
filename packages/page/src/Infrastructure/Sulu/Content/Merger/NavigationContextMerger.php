<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\Merger;

use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;

class NavigationContextMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof PageDimensionContentInterface) {
            return;
        }

        if (!$sourceObject instanceof PageDimensionContentInterface) {
            return;
        }

        if ($targetObject->getNavigationContexts() !== $sourceObject->getNavigationContexts()) {
            $targetObject->setNavigationContexts($sourceObject->getNavigationContexts());
        }
    }
}
