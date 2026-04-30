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

namespace Sulu\Content\Domain\Model;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

interface TaxonomyInterface
{
    /**
     * @return CategoryInterface[]
     */
    public function getExcerptCategories(): array;

    /**
     * @return int[]
     */
    public function getExcerptCategoryIds(): array;

    /**
     * @param CategoryInterface[] $excerptCategories
     */
    public function setExcerptCategories(array $excerptCategories): void;

    /**
     * @return TagInterface[]
     */
    public function getExcerptTags(): array;

    /**
     * @return string[]
     */
    public function getExcerptTagNames(): array;

    /**
     * @return int[]
     */
    public function getExcerptTagIds(): array;

    /**
     * @param TagInterface[] $excerptTags
     */
    public function setExcerptTags(array $excerptTags): void;

    /**
     * @return TargetGroupInterface[]
     */
    public function getExcerptAudienceTargetGroups(): array;

    /**
     * @return int[]
     */
    public function getExcerptAudienceTargetGroupIds(): array;

    /**
     * @param TargetGroupInterface[] $excerptAudienceTargetGroups
     */
    public function setExcerptAudienceTargetGroups(array $excerptAudienceTargetGroups): void;

    public function getExcerptSegment(): ?string;

    public function setExcerptSegment(?string $excerptSegment): void;
}
