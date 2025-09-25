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

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

trait ExcerptTrait
{
    /**
     * @var array<string, mixed>
     */
    private $excerptData = [];

    /**
     * @var ArrayCollection<int, CategoryInterface>
     */
    private $excerptCategories;

    /**
     * @var ArrayCollection<int, TagInterface>
     */
    private $excerptTags;

    public function getExcerptData(): array
    {
        return $this->excerptData;
    }

    /**
     * @param array<string, mixed> $excerptData
     */
    public function setExcerptData(array $excerptData): void
    {
        $this->excerptData = $excerptData;
    }

    /**
     * @return int[]
     */
    public function getExcerptCategoryIds(): array
    {
        $this->initializeCategories();
        $categoryIds = [];
        foreach ($this->excerptCategories as $excerptCategory) {
            $categoryIds[] = $excerptCategory->getId();
        }

        return $categoryIds;
    }

    /**
     * @return CategoryInterface[]
     */
    public function getExcerptCategories(): array
    {
        $this->initializeCategories();

        return $this->excerptCategories->toArray();
    }

    /**
     * @param CategoryInterface[] $excerptCategories
     */
    public function setExcerptCategories(array $excerptCategories): void
    {
        $this->initializeCategories();
        $this->excerptCategories->clear();

        foreach ($excerptCategories as $excerptCategory) {
            $this->excerptCategories->add($excerptCategory);
        }
    }

    /**
     * @return TagInterface[]
     */
    public function getExcerptTags(): array
    {
        $this->initializeTags();

        return $this->excerptTags->toArray();
    }

    /**
     * @return string[]
     */
    public function getExcerptTagNames(): array
    {
        $this->initializeTags();
        $tagNames = [];
        foreach ($this->excerptTags as $excerptTag) {
            $tagNames[] = $excerptTag->getName();
        }

        return $tagNames;
    }

    /**
     * @param TagInterface[] $excerptTags
     */
    public function setExcerptTags(array $excerptTags): void
    {
        $this->initializeTags();
        $this->excerptTags->clear();

        foreach ($excerptTags as $excerptTag) {
            $this->excerptTags->add($excerptTag);
        }
    }

    private function initializeTags(): void
    {
        if (null === $this->excerptTags) {
            $this->excerptTags = new ArrayCollection();
        }
    }

    private function initializeCategories(): void
    {
        if (null === $this->excerptCategories) {
            $this->excerptCategories = new ArrayCollection();
        }
    }
}
