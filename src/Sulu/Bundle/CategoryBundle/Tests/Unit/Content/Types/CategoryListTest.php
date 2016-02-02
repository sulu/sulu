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

use Prophecy\Argument;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation as CategoryTranslationEntity;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\StructureInterface;

class CategoryListTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContentData()
    {
        $categoryEntity1 = new CategoryEntity();
        $categoryTranslation1 = new CategoryTranslationEntity();
        $categoryTranslation1->setLocale('en');
        $categoryTranslation1->setTranslation('Category 1');
        $categoryEntity1->addTranslation($categoryTranslation1);
        $categoryEntity2 = new CategoryEntity();
        $categoryTranslation2 = new CategoryTranslationEntity();
        $categoryTranslation2->setLocale('en');
        $categoryTranslation2->setTranslation('Category 2');
        $categoryEntity2->addTranslation($categoryTranslation2);
        $category1 = new Category($categoryEntity1, 'en');
        $category2 = new Category($categoryEntity2, 'en');

        $categoryManager = $this->prophesize(CategoryManagerInterface::class);

        $categoryManager->findByIds([1, 2])->willReturn([$categoryEntity1, $categoryEntity2]);
        $categoryManager->getApiObjects([$categoryEntity1, $categoryEntity2], 'de')->willReturn([$category1, $category2]);

        $categoryList = new CategoryList($categoryManager->reveal(), '');

        $structure = $this->prophesize(StructureInterface::class);
        $structure->getLanguageCode()->willReturn('de');

        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn([1, 2]);
        $property->getStructure()->willReturn($structure->reveal());

        $result = $categoryList->getContentData($property->reveal());

        $this->assertEquals([$category1->toArray(), $category2->toArray()], $result);

        $categoryManager->findByIds([1, 2])->shouldBeCalled();
        $categoryManager->getApiObjects([$categoryEntity1, $categoryEntity2], 'de')->shouldBeCalled();
    }

    public function testGetContentDataNullPropertyValue()
    {
        $categoryManager = $this->prophesize(CategoryManagerInterface::class);

        $categoryList = new CategoryList($categoryManager->reveal(), '');

        $property = $this->prophesize(Property::class);
        $property->getValue()->willReturn(null);

        $result = $categoryList->getContentData($property->reveal());

        $this->assertEquals([], $result);

        $categoryManager->findByIds(Argument::any())->shouldNotBeCalled();
        $categoryManager->getApiObjects(Argument::any(), Argument::any())->shouldNotBeCalled();
    }
}
