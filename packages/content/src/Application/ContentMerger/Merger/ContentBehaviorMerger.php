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

namespace Sulu\Content\Application\ContentMerger\Merger;

use Sulu\Content\Domain\Model\ContentBehaviorInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class ContentBehaviorMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof ContentBehaviorInterface) {
            return;
        }

        if (!$sourceObject instanceof ContentBehaviorInterface) {
            return;
        }

        $targetObject->setBehavior($sourceObject->getBehavior());

        if ($behaviorData = $sourceObject->getBehaviorData()) {
            $targetObject->setBehaviorData($behaviorData);
        }
    }
}
