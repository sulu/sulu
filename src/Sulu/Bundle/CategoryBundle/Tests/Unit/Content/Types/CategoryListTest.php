<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Content\Types;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Prophecy\Argument;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class CategoryListTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryList
     */
    private $categoryList;

    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function setUp()
    {
        $this->categoryManager = $this->prophesize(CategoryManagerInterface::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);

        $this->categoryList = new CategoryList(
            $this->categoryManager->reveal(),
            $this->serializer->reveal(),
            'category-list-template'
        );
    }

    public function testGetContentData()
    {
        $category1 = $this->prophesize(CategoryInterface::class);
        $category2 = $this->prophesize(CategoryInterface::class);

        $this->categoryManager->findByIds([1, 2])->willReturn([$category1, $category2]);

        $this->serializer->serialize($category1, 'array', Argument::type(SerializationContext::class))
            ->willReturn('someArrayData');
        $this->serializer->serialize($category2, 'array', Argument::type(SerializationContext::class))
            ->willReturn('someOtherArrayData');

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn([1, 2]);
        $property->getStructure()->willReturn($structure->reveal());

        $result = $this->categoryList->getContentData($property->reveal());

        $this->assertEquals(['someArrayData', 'someOtherArrayData'], $result);
    }

    public function testGetContentDataNullPropertyValue()
    {
        $this->categoryManager->findByIds(Argument::any())->shouldNotBeCalled();

        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn(null);

        $result = $this->categoryList->getContentData($property->reveal());

        $this->assertEquals([], $result);
    }
}
