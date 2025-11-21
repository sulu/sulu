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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentMerger\Merger;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Content\Application\ContentMerger\Merger\TaxonomyMerger;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TaxonomyInterface;

class TaxonomyMergerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    protected function getTaxonomyMergerInstance(): MergerInterface
    {
        return new TaxonomyMerger();
    }

    public function testMergeSourceNotImplementTaxonomyInterface(): void
    {
        $merger = $this->getTaxonomyMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(TaxonomyInterface::class);
        $target->setExcerptSegment(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeTargetNotImplementTaxonomyInterface(): void
    {
        $merger = $this->getTaxonomyMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(TaxonomyInterface::class);
        $source->getExcerptSegment(Argument::any())->shouldNotBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeSet(): void
    {
        $merger = $this->getTaxonomyMergerInstance();

        $tag1 = $this->prophesize(TagInterface::class);
        $tag1->getId()->willReturn(1);
        $tag2 = $this->prophesize(TagInterface::class);
        $tag2->getId()->willReturn(2);

        $category1 = $this->prophesize(CategoryInterface::class);
        $category1->getId()->willReturn(3);
        $category2 = $this->prophesize(CategoryInterface::class);
        $category2->getId()->willReturn(4);

        $targetGroup1 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup1->getId()->willReturn(5);
        $targetGroup2 = $this->prophesize(TargetGroupInterface::class);
        $targetGroup2->getId()->willReturn(6);

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(TaxonomyInterface::class);
        $source->getExcerptSegment()->willReturn('test-segment')->shouldBeCalled();
        $source->getExcerptTags()->willReturn([$tag1->reveal(), $tag2->reveal()])->shouldBeCalled();
        $source->getExcerptCategories()->willReturn([$category1->reveal(), $category2->reveal()])->shouldBeCalled();
        $source->getExcerptAudienceTargetGroups()->willReturn([$targetGroup1->reveal(), $targetGroup2->reveal()])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(TaxonomyInterface::class);
        $target->setExcerptSegment('test-segment')->shouldBeCalled();
        $target->setExcerptTags(Argument::that(function(array $tags) {
            /** @var TagInterface[] $tags */
            return \array_map(function(TagInterface $tag) {
                return $tag->getId();
            }, $tags) === [1, 2];
        }))->shouldBeCalled();
        $target->setExcerptCategories(Argument::that(function(array $categories) {
            /** @var CategoryInterface[] $categories */
            return \array_map(function(CategoryInterface $category) {
                return $category->getId();
            }, $categories) === [3, 4];
        }))->shouldBeCalled();
        $target->setExcerptAudienceTargetGroups(Argument::that(function(array $targetGroups) {
            /** @var TargetGroupInterface[] $targetGroups */
            return \array_map(function(TargetGroupInterface $targetGroup) {
                return $targetGroup->getId();
            }, $targetGroups) === [5, 6];
        }))->shouldBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }

    public function testMergeNotSet(): void
    {
        $merger = $this->getTaxonomyMergerInstance();

        $source = $this->prophesize(DimensionContentInterface::class);
        $source->willImplement(TaxonomyInterface::class);
        $source->getExcerptSegment()->willReturn(null)->shouldBeCalled();
        $source->getExcerptTags()->willReturn([])->shouldBeCalled();
        $source->getExcerptCategories()->willReturn([])->shouldBeCalled();
        $source->getExcerptAudienceTargetGroups()->willReturn([])->shouldBeCalled();

        $target = $this->prophesize(DimensionContentInterface::class);
        $target->willImplement(TaxonomyInterface::class);
        $target->setExcerptSegment(Argument::any())->shouldNotBeCalled();
        $target->setExcerptTags(Argument::any())->shouldNotBeCalled();
        $target->setExcerptCategories(Argument::any())->shouldNotBeCalled();
        $target->setExcerptAudienceTargetGroups(Argument::any())->shouldNotBeCalled();

        $merger->merge($target->reveal(), $source->reveal());
    }
}
