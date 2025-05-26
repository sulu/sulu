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

use Sulu\Content\Domain\Model\AuditableInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class AuditableMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof AuditableInterface) {
            return;
        }

        if (!$sourceObject instanceof AuditableInterface) {
            return;
        }

        $changed = $sourceObject->getChanged();
        $targetObject->setChanged($changed);

        $created = $sourceObject->getCreated();
        $targetObject->setCreated($created);

        if ($creator = $sourceObject->getCreator()) {
            $targetObject->setCreator($creator);
        }

        if ($changer = $sourceObject->getChanger()) {
            $targetObject->setChanger($changer);
        }
    }
}
