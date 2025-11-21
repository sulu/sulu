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

use Sulu\Content\Domain\Model\ExcerptInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class ExcerptMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof ExcerptInterface) {
            return;
        }

        if (!$sourceObject instanceof ExcerptInterface) {
            return;
        }

        $targetObject->setExcerptData(\array_merge(
            $targetObject->getExcerptData(),
            $sourceObject->getExcerptData()
        ));
    }
}
