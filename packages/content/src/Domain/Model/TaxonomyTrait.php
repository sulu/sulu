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
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

trait TaxonomyTrait
{
    /**
     * @var ArrayCollection<int, CategoryInterface>
     */
    private $excerptCategories;

    /**
     * @var ArrayCollection<int, TagInterface>
     */
    private $excerptTags;

    /**
     * @var ArrayCollection<int, TargetGroupInterface>
     */
    private $excerptAudienceTargetGroups;

    private ?string $excerptSegment = null;

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

        foreach ($this->excerptCategories as $existingCategory) {
            if (!\in_array($existingCategory, $excerptCategories, true)) {
                $this->excerptCategories->removeElement($existingCategory);
            }
        }

        foreach ($excerptCategories as $newCategory) {
            if (!$this->excerptCategories->contains($newCategory)) {
                $this->excerptCategories->add($newCategory);
            }
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
     * @return int[]
     */
    public function getExcerptTagIds(): array
    {
        $this->initializeTags();
        $tagIds = [];
        foreach ($this->excerptTags as $excerptTag) {
            $tagIds[] = $excerptTag->getId();
        }

        return $tagIds;
    }

    /**
     * @param TagInterface[] $excerptTags
     */
    public function setExcerptTags(array $excerptTags): void
    {
        $this->initializeTags();

        foreach ($this->excerptTags as $existingTag) {
            if (!\in_array($existingTag, $excerptTags, true)) {
                $this->excerptTags->removeElement($existingTag);
            }
        }

        foreach ($excerptTags as $newTag) {
            if (!$this->excerptTags->contains($newTag)) {
                $this->excerptTags->add($newTag);
            }
        }
    }

    public function getExcerptAudienceTargetGroups(): array
    {
        $this->initializeAudienceTargetGroups();

        return $this->excerptAudienceTargetGroups->toArray();
    }

    public function getExcerptAudienceTargetGroupIds(): array
    {
        $this->initializeAudienceTargetGroups();
        $targetGroupIds = [];
        foreach ($this->excerptAudienceTargetGroups as $excerptAudienceTargetGroup) {
            $targetGroupIds[] = $excerptAudienceTargetGroup->getId();
        }

        return $targetGroupIds;
    }

    public function setExcerptAudienceTargetGroups(array $excerptAudienceTargetGroups): void
    {
        $this->initializeAudienceTargetGroups();

        // Remove target groups no longer in the new list
        foreach ($this->excerptAudienceTargetGroups as $existingTargetGroup) {
            if (!\in_array($existingTargetGroup, $excerptAudienceTargetGroups, true)) {
                $this->excerptAudienceTargetGroups->removeElement($existingTargetGroup);
            }
        }

        // Add new target groups
        foreach ($excerptAudienceTargetGroups as $newTargetGroup) {
            if (!$this->excerptAudienceTargetGroups->contains($newTargetGroup)) {
                $this->excerptAudienceTargetGroups->add($newTargetGroup);
            }
        }
    }

    public function getExcerptSegment(): ?string
    {
        return $this->excerptSegment;
    }

    public function setExcerptSegment(?string $excerptSegment): void
    {
        $this->excerptSegment = $excerptSegment;
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

    private function initializeAudienceTargetGroups(): void
    {
        if (null === $this->excerptAudienceTargetGroups) {
            $this->excerptAudienceTargetGroups = new ArrayCollection();
        }
    }
}
