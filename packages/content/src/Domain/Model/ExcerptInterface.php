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

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

interface ExcerptInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getExcerptData(): array;

    /**
     * @param array<string, mixed> $excerptData
     */
    public function setExcerptData(array $excerptData): void;

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
     * @param TagInterface[] $excerptTags
     */
    public function setExcerptTags(array $excerptTags): void;
}
