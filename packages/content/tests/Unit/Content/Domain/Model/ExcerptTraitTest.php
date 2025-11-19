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

namespace Sulu\Content\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\ExcerptTrait;

class ExcerptTraitTest extends TestCase
{
    use SetGetPrivatePropertyTrait;

    protected function getExcerptInstance(): ExcerptInterface
    {
        return new class() implements ExcerptInterface {
            use ExcerptTrait;
        };
    }

    public function testGetSetExcerptTitle(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptTitle());
        $model->setExcerptTitle('Excerpt Title');
        $this->assertSame('Excerpt Title', $model->getExcerptTitle());
    }

    public function testGetSetExcerptDescription(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptDescription());
        $model->setExcerptDescription('Excerpt Description');
        $this->assertSame('Excerpt Description', $model->getExcerptDescription());
    }

    public function testGetSetExcerptMore(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptMore());
        $model->setExcerptMore('Excerpt More');
        $this->assertSame('Excerpt More', $model->getExcerptMore());
    }

    public function testGetSetExcerptImageId(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptImage());
        $model->setExcerptImage(['id' => 1]);
        $this->assertNotNull($model->getExcerptImage());
        $this->assertSame(['id' => 1], $model->getExcerptImage());
    }

    public function testGetSetExcerptIconId(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptIcon());
        $model->setExcerptIcon(['id' => 2]);
        $this->assertNotNull($model->getExcerptIcon());
        $this->assertSame(['id' => 2], $model->getExcerptIcon());
    }

    public function testGetSetExcerptTags(): void
    {
        $tag1 = $this->createTag(1);
        $tag2 = $this->createTag(2);

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptTags());
        $model->setExcerptTags([$tag1, $tag2]);
        $this->assertSame([1, 2], \array_map(function(TagInterface $tag) {
            return $tag->getId();
        }, $model->getExcerptTags()));
    }

    public function testGetSetExcerptCategories(): void
    {
        $category1 = $this->createCategory(1);
        $category2 = $this->createCategory(2);

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptCategories());
        $model->setExcerptCategories([$category1, $category2]);
        $this->assertSame([1, 2], \array_map(function(CategoryInterface $category) {
            return $category->getId();
        }, $model->getExcerptCategories()));
    }

    public function testGetSetExcerptSegment(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptSegment());
        $model->setExcerptSegment('test-segment');
        $this->assertSame('test-segment', $model->getExcerptSegment());
    }

    public function testGetSetExcerptAudienceTargetGroups(): void
    {
        $targetGroup1 = $this->createTargetGroup(1);
        $targetGroup2 = $this->createTargetGroup(2);

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptAudienceTargetGroups());
        $model->setExcerptAudienceTargetGroups([$targetGroup1, $targetGroup2]);
        $this->assertSame([1, 2], \array_map(function(TargetGroupInterface $targetGroup) {
            return $targetGroup->getId();
        }, $model->getExcerptAudienceTargetGroups()));
    }

    public function testGetExcerptAudienceTargetGroupIds(): void
    {
        $targetGroup1 = $this->createTargetGroup(5);
        $targetGroup2 = $this->createTargetGroup(6);
        $targetGroup3 = $this->createTargetGroup(7);

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptAudienceTargetGroupIds());
        $model->setExcerptAudienceTargetGroups([$targetGroup1, $targetGroup2, $targetGroup3]);
        $this->assertSame([5, 6, 7], $model->getExcerptAudienceTargetGroupIds());
    }

    public function testGetExcerptTagNames(): void
    {
        $tag1 = $this->createTag(1);
        $tag1->setName('Tag 1');
        $tag2 = $this->createTag(2);
        $tag2->setName('Tag 2');

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptTagNames());
        $model->setExcerptTags([$tag1, $tag2]);
        $this->assertSame(['Tag 1', 'Tag 2'], $model->getExcerptTagNames());
    }

    public function testGetExcerptCategoryIds(): void
    {
        $category1 = $this->createCategory(3);
        $category2 = $this->createCategory(4);

        $model = $this->getExcerptInstance();
        $this->assertEmpty($model->getExcerptCategoryIds());
        $model->setExcerptCategories([$category1, $category2]);
        $this->assertSame([3, 4], $model->getExcerptCategoryIds());
    }

    public function testSetExcerptTagsSynchronizesCollection(): void
    {
        $tag1 = $this->createTag(1);
        $tag2 = $this->createTag(2);
        $tag3 = $this->createTag(3);

        $model = $this->getExcerptInstance();

        $model->setExcerptTags([$tag1, $tag2]);
        $this->assertSame([1, 2], \array_values(\array_map(function(TagInterface $tag) {
            return $tag->getId();
        }, $model->getExcerptTags())));

        $model->setExcerptTags([$tag1, $tag3]);
        $this->assertSame([1, 3], \array_values(\array_map(function(TagInterface $tag) {
            return $tag->getId();
        }, $model->getExcerptTags())));

        $this->assertCount(2, $model->getExcerptTags());
    }

    public function testSetExcerptCategoriesSynchronizesCollection(): void
    {
        $category1 = $this->createCategory(1);
        $category2 = $this->createCategory(2);
        $category3 = $this->createCategory(3);

        $model = $this->getExcerptInstance();

        $model->setExcerptCategories([$category1, $category2]);
        $this->assertSame([1, 2], \array_values(\array_map(function(CategoryInterface $category) {
            return $category->getId();
        }, $model->getExcerptCategories())));

        $model->setExcerptCategories([$category1, $category3]);
        $this->assertSame([1, 3], \array_values(\array_map(function(CategoryInterface $category) {
            return $category->getId();
        }, $model->getExcerptCategories())));

        $this->assertCount(2, $model->getExcerptCategories());
    }

    public function testSetExcerptAudienceTargetGroupsSynchronizesCollection(): void
    {
        $targetGroup1 = $this->createTargetGroup(1);
        $targetGroup2 = $this->createTargetGroup(2);
        $targetGroup3 = $this->createTargetGroup(3);

        $model = $this->getExcerptInstance();

        $model->setExcerptAudienceTargetGroups([$targetGroup1, $targetGroup2]);
        $this->assertSame([1, 2], \array_values(\array_map(function(TargetGroupInterface $targetGroup) {
            return $targetGroup->getId();
        }, $model->getExcerptAudienceTargetGroups())));

        $model->setExcerptAudienceTargetGroups([$targetGroup1, $targetGroup3]);
        $this->assertSame([1, 3], \array_values(\array_map(function(TargetGroupInterface $targetGroup) {
            return $targetGroup->getId();
        }, $model->getExcerptAudienceTargetGroups())));

        $this->assertCount(2, $model->getExcerptAudienceTargetGroups());
    }

    public function testSharedTagInstancesAcrossMultipleModels(): void
    {
        $tag1 = $this->createTag(1);
        $tag2 = $this->createTag(2);

        $model1 = $this->getExcerptInstance();
        $model2 = $this->getExcerptInstance();

        $model1->setExcerptTags([$tag1, $tag2]);
        $model2->setExcerptTags([$tag1, $tag2]);

        $this->assertCount(2, $model1->getExcerptTags());
        $this->assertCount(2, $model2->getExcerptTags());

        $model2->setExcerptTags([$tag1]);

        $this->assertCount(2, $model1->getExcerptTags());
        $this->assertSame([1, 2], \array_values(\array_map(function(TagInterface $tag) {
            return $tag->getId();
        }, $model1->getExcerptTags())));

        $this->assertCount(1, $model2->getExcerptTags());
        $this->assertSame([1], \array_values(\array_map(function(TagInterface $tag) {
            return $tag->getId();
        }, $model2->getExcerptTags())));
    }

    private function createTag(int $id): TagInterface
    {
        $tag = new Tag();
        $tag->setId($id);

        return $tag;
    }

    private function createCategory(int $id): CategoryInterface
    {
        $category = new Category();
        $category->setId($id);

        return $category;
    }

    private function createTargetGroup(int $id): TargetGroupInterface
    {
        $targetGroup = new TargetGroup();
        $this->setPrivateProperty($targetGroup, 'id', $id);

        return $targetGroup;
    }
}
