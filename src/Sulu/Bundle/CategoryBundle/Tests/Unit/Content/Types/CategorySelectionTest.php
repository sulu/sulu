<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Content\Types;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Content\Types\CategorySelection;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class CategorySelectionTest extends TestCase
{
    use ProphecyTrait;

    public function testGetContentData(): void
    {
        $entity1 = $this->prophesize(CategoryInterface::class);
        $entity2 = $this->prophesize(CategoryInterface::class);

        $category1 = $this->prophesize(Category::class);
        $category1->toArray()->willReturn('someArrayData');

        $category2 = $this->prophesize(Category::class);
        $category2->toArray()->willReturn('someOtherArrayData');

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn([1, 2]);
        $property->getStructure()->willReturn($structure->reveal());

        $categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $categoryManager->findByIds([1, 2])->willReturn([$entity1, $entity2]);
        $categoryManager->getApiObjects([$entity1, $entity2], 'de')->willReturn([$category1, $category2]);

        $categoryList = new CategorySelection($categoryManager->reveal());

        $result = $categoryList->getContentData($property->reveal());

        $this->assertEquals(['someArrayData', 'someOtherArrayData'], $result);
    }

    public function testGetContentDataNullPropertyValue(): void
    {
        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn(null);

        $categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $categoryManager->findByIds(Argument::any())->shouldNotBeCalled();

        $categoryList = new CategorySelection($categoryManager->reveal());

        $result = $categoryList->getContentData($property->reveal());

        $this->assertEquals([], $result);
    }
}
