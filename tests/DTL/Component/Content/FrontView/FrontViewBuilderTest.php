<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\FrontView;

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\FrontView\FrontViewBuilder;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Prophecy\Argument;
use DTL\Component\Content\Structure\Structure;
use DTL\Component\Content\Structure\Property;

class FrontViewBuilderTest extends ProphecyTestCase
{
    /**
     * @var FrontView
     */
    private $builder;

    /**
     * @var ContentFormFactoryInterface
     */
    private $structureFactory;

    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var FormTypeInterface[]
     */
    private $propertyTypes;

    public function setUp()
    {
        parent::setUp();

        $this->structureFactory = $this->prophesize('DTL\Component\Content\Structure\Factory\StructureFactoryInterface');
        $this->document1 = $this->prophesize('DTL\Component\Content\Document\DocumentInterface');
        $this->propertyTypeRegistry = $this->prophesize('DTL\Component\Content\Property\PropertyTypeRegistryInterface');
        $this->structure = new Structure();
        $this->builder = new FrontViewBuilder(
            $this->structureFactory->reveal(),
            $this->propertyTypeRegistry->reveal()
        );
    }

    public function testBuildFor()
    {
        $structureType = 'example';
        $data = array(
            'one' => 'hello',
            'two' => 'world',
        );
        $properties = array(
            'one' => array('type' => 'text_line'),
            'two' => array('type' => 'text_line'),
        );

        $propertyTypes = array();

        $parentType = $this->prophesize('DTL\Component\Content\Property\PropertyTypeInterface');
        $parentType->getParent()->willReturn(null);
        $parentType->buildFrontView(Argument::type('DTL\Component\Content\FrontView\FrontView'), $data['one'], array())->shouldBeCalled();
        $parentType->buildFrontView(Argument::type('DTL\Component\Content\FrontView\FrontView'), $data['two'], array())->shouldBeCalled();
        $parentType->setDefaultOptions(
            Argument::type('Symfony\Component\OptionsResolver\OptionsResolverInterface')
        )->shouldBeCalled();

        $textLineType = $this->prophesize('DTL\Component\Content\Property\PropertyTypeInterface');
        $textLineType->getParent()->willReturn('parent');
        $textLineType->buildFrontView(Argument::type('DTL\Component\Content\FrontView\FrontView'), $data['one'], array())->shouldBeCalled();
        $textLineType->buildFrontView(Argument::type('DTL\Component\Content\FrontView\FrontView'), $data['two'], array())->shouldBeCalled();
        $textLineType->setDefaultOptions(
            Argument::type('Symfony\Component\OptionsResolver\OptionsResolverInterface')
        )->shouldBeCalled();

        $this->propertyTypeRegistry->getProperty('text_line')->willReturn($textLineType->reveal());
        $this->propertyTypeRegistry->getProperty('parent')->willReturn($parentType);

        $this->document1->getStructureType()->willReturn($structureType);
        $this->document1->getDocumentType()->willReturn('page');
        $this->document1->getContent()->willReturn($data);
        $this->structureFactory->getStructure('page', $structureType)->willReturn($this->structure);

        foreach ($properties as $name => $propertyData) {
            $property = new Property();
            foreach ($propertyData as $attrName => $attrValue) {
                $property->$attrName = $attrValue;
            }
            $this->structure->properties[$name] = $property;
        }

        $this->builder->buildFor(
            $this->document1->reveal()
        );
    }
}
