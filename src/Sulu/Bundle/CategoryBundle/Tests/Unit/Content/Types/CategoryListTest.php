<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Content\Types;

use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category as CategoryEntity;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation as CategoryTranslationEntity;

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

    public function setUp()
    {
        $this->categoryManager = $this->getMockBuilder('Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface')
            ->getMock();
        $this->categoryList = new CategoryList($this->categoryManager, '');
    }

    public function testRead()
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

        $this->categoryManager->expects($this->any())->method('findByIds')->will($this->returnValueMap(
                array(
                    array(array(1, 2), array($categoryEntity1, $categoryEntity2)),
                )
            )
        );

        $this->categoryManager->expects($this->any())->method('getApiObjects')->will($this->returnValueMap(
                array(
                    array(array($categoryEntity1, $categoryEntity2), 'en', array($category1, $category2)),
                )
            )
        );

        $node = $this->getMockBuilder('PHPCR\NodeInterface')
            ->getMock();
        $node->expects($this->any())->method('getPropertyValueWithDefault')->will($this->returnValueMap(
                array(
                    array('property', array(), array(1, 2)),
                )
            )
        );

        $property = $this->getMockBuilder('Sulu\Component\Content\PropertyInterface')
            ->getMock();
        $property->expects($this->any())->method('getName')->willReturn('property');
        $property->expects($this->any())->method('setValue')->with(array($category1->toArray(), $category2->toArray()));

        $this->categoryManager->expects($this->once())->method('findByIds');
        $this->categoryList->read($node, $property, null, 'en', null);
    }
}
