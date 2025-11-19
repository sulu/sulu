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

use Sulu\Content\Domain\Model\TaxonomyInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own merger instead.
 */
final class TaxonomyMerger implements MergerInterface
{
    public function merge(object $targetObject, object $sourceObject): void
    {
        if (!$targetObject instanceof TaxonomyInterface) {
            return;
        }

        if (!$sourceObject instanceof TaxonomyInterface) {
            return;
        }

        if ($excerptSegment = $sourceObject->getExcerptSegment()) {
            $targetObject->setExcerptSegment($excerptSegment);
        }

        if ($excerptTags = $sourceObject->getExcerptTags()) {
            if (\count($excerptTags) > 0) { // @phpstan-ignore-line false positive for phpstan thinks it is a non-empty-array
                $targetObject->setExcerptTags($excerptTags);
            }
        }

        if ($excerptCategories = $sourceObject->getExcerptCategories()) {
            if (\count($excerptCategories) > 0) { // @phpstan-ignore-line false positive for phpstan thinks it is a non-empty-array
                $targetObject->setExcerptCategories($excerptCategories);
            }
        }

        if ($excerptAudienceTargetGroups = $sourceObject->getExcerptAudienceTargetGroups()) {
            if (\count($excerptAudienceTargetGroups) > 0) { // @phpstan-ignore-line false positive for phpstan thinks it is a non-empty-array
                $targetObject->setExcerptAudienceTargetGroups($excerptAudienceTargetGroups);
            }
        }
    }
}
