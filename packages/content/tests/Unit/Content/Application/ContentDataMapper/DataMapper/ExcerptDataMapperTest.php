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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentDataMapper\DataMapper\ExcerptDataMapper;
use Sulu\Content\Domain\Factory\CategoryFactoryInterface;
use Sulu\Content\Domain\Factory\TagFactoryInterface;
use Sulu\Content\Domain\Factory\TargetGroupFactoryInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ExcerptDataMapperTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<TagFactoryInterface>
     */
    private $tagFactory;

    /**
     * @var ObjectProphecy<CategoryFactoryInterface>
     */
    private $categoryFactory;

    /**
     * @var ObjectProphecy<TargetGroupFactoryInterface>
     */
    private $targetGroupFactory;

    protected function setUp(): void
    {
        $this->tagFactory = $this->prophesize(TagFactoryInterface::class);
        $this->categoryFactory = $this->prophesize(CategoryFactoryInterface::class);
        $this->targetGroupFactory = $this->prophesize(TargetGroupFactoryInterface::class);
    }

    protected function createExcerptDataMapperInstance(): ExcerptDataMapper
    {
        return new ExcerptDataMapper($this->tagFactory->reveal(), $this->categoryFactory->reveal(), $this->targetGroupFactory->reveal());
    }

    public function testMapNoExcerptInterface(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $this->tagFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $this->categoryFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
    }

    public function testMapNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $this->tagFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $this->categoryFactory->create(Argument::any())
            ->shouldNotBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getExcerptTitle());
        $this->assertNull($localizedDimensionContent->getExcerptDescription());
        $this->assertNull($localizedDimensionContent->getExcerptIcon());
        $this->assertNull($localizedDimensionContent->getExcerptImage());
        $this->assertCount(0, $localizedDimensionContent->getExcerptTagNames());
        $this->assertCount(0, $localizedDimensionContent->getExcerptCategoryIds());
    }

    public function testMapUnlocalizedExcerpt(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $tag1 = new Tag();
        $tag1->setName('Tag 1');
        $tag2 = new Tag();
        $tag2->setName('Tag 2');

        $this->tagFactory->create(['Tag 1', 'Tag 2'])->willReturn([$tag1, $tag2])->shouldBeCalled();

        $category1 = new Category();
        $category1->setId(3);
        $category2 = new Category();
        $category2->setId(4);
        $this->categoryFactory->create([3, 4])->willReturn([$category1, $category2])->shouldBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Excerpt Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertSame(['id' => 1], $localizedDimensionContent->getExcerptImage());
        $this->assertSame(['id' => 2], $localizedDimensionContent->getExcerptIcon());
        $this->assertSame(['Tag 1', 'Tag 2'], $localizedDimensionContent->getExcerptTagNames());
        $this->assertSame([3, 4], $localizedDimensionContent->getExcerptCategoryIds());
    }

    public function testMapWithSegment(): void
    {
        $data = [
            'excerptSegment' => 'test-segment',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('test-segment', $localizedDimensionContent->getExcerptSegment());
    }

    public function testMapWithSegmentArray(): void
    {
        $data = [
            'excerptSegment' => ['webspace-key' => 'test-segment'],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('test-segment', $localizedDimensionContent->getExcerptSegment());
    }

    public function testMapWithAudienceTargetGroups(): void
    {
        $data = [
            'excerptAudienceTargetGroups' => [5, 6, 7],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $targetGroup1 = new TargetGroup();
        $this->setPrivateProperty($targetGroup1, 'id', 5);
        $targetGroup2 = new TargetGroup();
        $this->setPrivateProperty($targetGroup2, 'id', 6);
        $targetGroup3 = new TargetGroup();
        $this->setPrivateProperty($targetGroup3, 'id', 7);

        $this->targetGroupFactory->create([5, 6, 7])->willReturn([$targetGroup1, $targetGroup2, $targetGroup3])->shouldBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([5, 6, 7], $localizedDimensionContent->getExcerptAudienceTargetGroupIds());
    }

    public function testMapWithAllData(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptDescription' => 'Excerpt Description',
            'excerptMore' => 'Excerpt More',
            'excerptSegment' => 'test-segment',
            'excerptImage' => ['id' => 1],
            'excerptIcon' => ['id' => 2],
            'excerptTags' => ['Tag 1', 'Tag 2'],
            'excerptCategories' => [3, 4],
            'excerptAudienceTargetGroups' => [5, 6],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $tag1 = new Tag();
        $tag1->setName('Tag 1');
        $tag2 = new Tag();
        $tag2->setName('Tag 2');

        $this->tagFactory->create(['Tag 1', 'Tag 2'])->willReturn([$tag1, $tag2])->shouldBeCalled();

        $category1 = new Category();
        $category1->setId(3);
        $category2 = new Category();
        $category2->setId(4);
        $this->categoryFactory->create([3, 4])->willReturn([$category1, $category2])->shouldBeCalled();

        $targetGroup1 = new TargetGroup();
        $this->setPrivateProperty($targetGroup1, 'id', 5);
        $targetGroup2 = new TargetGroup();
        $this->setPrivateProperty($targetGroup2, 'id', 6);
        $this->targetGroupFactory->create([5, 6])->willReturn([$targetGroup1, $targetGroup2])->shouldBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('Excerpt Description', $localizedDimensionContent->getExcerptDescription());
        $this->assertSame('Excerpt More', $localizedDimensionContent->getExcerptMore());
        $this->assertSame('test-segment', $localizedDimensionContent->getExcerptSegment());
        $this->assertSame(['id' => 1], $localizedDimensionContent->getExcerptImage());
        $this->assertSame(['id' => 2], $localizedDimensionContent->getExcerptIcon());
        $this->assertSame(['Tag 1', 'Tag 2'], $localizedDimensionContent->getExcerptTagNames());
        $this->assertSame([3, 4], $localizedDimensionContent->getExcerptCategoryIds());
        $this->assertSame([5, 6], $localizedDimensionContent->getExcerptAudienceTargetGroupIds());
    }

    public function testMapWithAudienceTargetGroupsWhenTargetGroupFactoryIsNull(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptSegment' => 'test-segment',
            'excerptAudienceTargetGroups' => [5, 6, 7],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        // Create mapper without target group factory (simulate when audience targeting bundle is not installed)
        $excerptMapper = new ExcerptDataMapper(
            $this->tagFactory->reveal(),
            $this->categoryFactory->reveal(),
            null // No target group factory
        );

        // Map should not throw an error even when target group factory is null
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        // Other fields should still be mapped correctly
        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame('test-segment', $localizedDimensionContent->getExcerptSegment());

        // Audience target groups should be empty when factory is null
        $this->assertSame([], $localizedDimensionContent->getExcerptAudienceTargetGroupIds());
    }

    public function testMapWithEmptyAudienceTargetGroups(): void
    {
        $data = [
            'excerptTitle' => 'Excerpt Title',
            'excerptAudienceTargetGroups' => [],
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $this->targetGroupFactory->create([])->willReturn([])->shouldBeCalled();

        $excerptMapper = $this->createExcerptDataMapperInstance();
        $excerptMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('Excerpt Title', $localizedDimensionContent->getExcerptTitle());
        $this->assertSame([], $localizedDimensionContent->getExcerptAudienceTargetGroupIds());
    }
}
