<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\ResourceMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Datagrid;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Field as DatagridField;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Field;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Form;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Option;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Section;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataMapper;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactory;
use Symfony\Component\Translation\Translator;

class ResourceMetadataMapperTest extends TestCase
{
    /**
     * @var ResourceMetadataMapper
     */
    private $resourceMetadataMapper;

    /**
     * @var FieldDescriptorFactory
     */
    private $fieldDescriptorFactory;

    /**
     * @var Translator
     */
    private $translator;

    public function setUp()
    {
        $this->fieldDescriptorFactory = $this->prophesize(FieldDescriptorFactory::class);

        $this->translator = $this->prophesize(Translator::class);
        $this->translator->trans('test_translation_key1', [], 'admin')->willReturn('Test 1');
        $this->translator->trans('test_translation_key2', [], 'admin')->willReturn('Test 2');
        $this->translator->trans('test_translation_key3', [], 'admin')->willReturn('Test 3');

        $this->resourceMetadataMapper = new ResourceMetadataMapper(
            $this->fieldDescriptorFactory->reveal(),
            $this->translator->reveal()
        );
    }

    public function testMapDatagrid()
    {
        $fieldDescriptors = [
            new FieldDescriptor('test1', 'test_translation_key1', FieldDescriptorInterface::VISIBILITY_YES, FieldDescriptorInterface::SEARCHABILITY_NEVER, 'string'),
            new FieldDescriptor('test2', 'test_translation_key2', FieldDescriptorInterface::VISIBILITY_NO, FieldDescriptorInterface::SEARCHABILITY_NEVER, 'int'),
            new FieldDescriptor('test3', 'test_translation_key3', FieldDescriptorInterface::VISIBILITY_NEVER, FieldDescriptorInterface::SEARCHABILITY_NEVER, 'string', '', '', false),
        ];
        $this->fieldDescriptorFactory->getFieldDescriptorForClass('TestClass')->willReturn($fieldDescriptors);

        /** @var Datagrid $datagrid */
        $datagrid = $this->resourceMetadataMapper->mapDatagrid('TestClass', 'de');

        $this->assertInstanceOf(Datagrid::class, $datagrid);

        $this->assertCount(3, $datagrid->getFields());

        /** @var DatagridField $field1 */
        $field1 = $datagrid->getFields()['test1'];
        $this->assertSame($field1->getName(), 'test1');
        $this->assertSame($field1->getLabel(), 'Test 1');
        $this->assertSame($field1->getType(), 'string');
        $this->assertSame($field1->isSortable(), true);

        /** @var DatagridField $field2 */
        $field2 = $datagrid->getFields()['test2'];
        $this->assertSame($field2->getName(), 'test2');
        $this->assertSame($field2->getLabel(), 'Test 2');
        $this->assertSame($field2->getType(), 'int');
        $this->assertSame($field2->isSortable(), true);

        /** @var DatagridField $field3 */
        $field3 = $datagrid->getFields()['test3'];
        $this->assertSame($field3->getName(), 'test3');
        $this->assertSame($field3->getLabel(), 'Test 3');
        $this->assertSame($field3->getType(), 'string');
        $this->assertSame($field3->isSortable(), false);
    }

    public function testMapSchema()
    {
        /** @var Schema $schema */
        $schema = $this->resourceMetadataMapper->mapSchema($this->getProperties('properties'));

        $this->assertInstanceOf(Schema::class, $schema);

        $this->assertCount(2, $schema->getRequired());
        $this->assertSame(['test2', 'test3'], $schema->getRequired());
    }

    public function testMapFormProperties()
    {
        /** @var Form $form */
        $form = $this->resourceMetadataMapper->mapForm($this->getProperties('properties'), 'de');

        $this->assertInstanceOf(Form::class, $form);

        $this->assertCount(3, $form->getItems());

        /** @var Field $field1 */
        $field1 = $form->getItems()['test1'];
        $this->assertSame($field1->getName(), 'test1');
        $this->assertSame($field1->getVisibilityCondition(), 'propertyVisibilityCondition');
        $this->assertSame($field1->getLabel(), 'Test 1');
        $this->assertSame($field1->getType(), 'text_line');

        /** @var Field $field2 */
        $field2 = $form->getItems()['test2'];
        $this->assertSame($field2->getName(), 'test2');
        $this->assertSame($field2->getLabel(), 'Test 2');
        $this->assertSame($field2->getType(), 'text_line');

        /** @var Field $field3 */
        $field3 = $form->getItems()['test3'];
        $this->assertSame($field3->getName(), 'test3');
        $this->assertSame($field3->getLabel(), 'Test 3');
        $this->assertSame($field3->getDescription(), 'Description 3');
        $this->assertSame($field3->getType(), 'single_select');
        $this->assertCount(2, $field3->getOptions());

        /** @var Option $option1 */
        $option1 = $field3->getOptions()['default_value'];
        $this->assertSame($option1->getName(), 'default_value');
        $this->assertSame($option1->getTitle(), 'Default value');
        $this->assertSame($option1->getInfotext(), 'Description of default value');
        $this->assertSame($option1->getValue(), 0);

        /** @var Option $option2 */
        $option2 = $field3->getOptions()['values'];
        $this->assertSame($option2->getName(), 'values');
        $this->assertSame($option2->getTitle(), null);
        $this->assertCount(2, $option2->getValue());

        $option2Value1 = $option2->getValue()[0];
        $this->assertSame($option2Value1->getName(), 'option1');
        $this->assertSame($option2Value1->getTitle(), 'Select Option 1');
        $this->assertSame($option2Value1->getValue(), 0);

        $option2Value2 = $option2->getValue()[1];
        $this->assertSame($option2Value2->getName(), 'option2');
        $this->assertSame($option2Value2->getTitle(), 'Select Option 2');
        $this->assertSame($option2Value2->getValue(), 1);
    }

    public function testMapFormBlock()
    {
        /** @var Form $form */
        $form = $this->resourceMetadataMapper->mapForm($this->getProperties('block'), 'de');

        $this->assertInstanceOf(Form::class, $form);

        $this->assertCount(2, $form->getItems());

        /** @var Field $field1 */
        $field1 = $form->getItems()['test1'];
        $this->assertSame($field1->getName(), 'test1');
        $this->assertSame($field1->getLabel(), 'Test 1');
        $this->assertSame($field1->getType(), 'text_line');

        /** @var Field $block */
        $block = $form->getItems()['blocktest'];
        $this->assertSame($block->getName(), 'blocktest');
        $this->assertSame($block->getVisibilityCondition(), 'blockVisibilityCondition');
        $this->assertSame($block->getLabel(), 'Block Test');
        $this->assertSame($block->getType(), 'block');
        $this->assertCount(2, $block->getTypes());

        $fieldType1 = $block->getTypes()['type1'];
        $this->assertSame($fieldType1->getName(), 'type1');
        $this->assertSame($fieldType1->getTitle(), 'Type 1');
        $this->assertCount(2, $fieldType1->getForm()->getItems());

        $fieldType2 = $block->getTypes()['type2'];
        $this->assertSame($fieldType2->getName(), 'type2');
        $this->assertSame($fieldType2->getTitle(), 'Type 2');
        $this->assertCount(1, $fieldType2->getForm()->getItems());
    }

    public function testMapFormSection()
    {
        /** @var Form $form */
        $form = $this->resourceMetadataMapper->mapForm($this->getProperties('section'), 'de');

        $this->assertInstanceOf(Form::class, $form);

        $this->assertCount(1, $form->getItems());

        /** @var Section $section */
        $section = $form->getItems()['sectiontest'];
        $this->assertSame('sectiontest', $section->getName());
        $this->assertSame($section->getVisibilityCondition(), 'sectionVisibilityCondition');
        $this->assertSame('Section Title', $section->getLabel());
        $this->assertCount(4, $section->getItems());
        $this->assertArrayHasKey('test1', $section->getItems());
        $this->assertArrayHasKey('test2', $section->getItems());
        $this->assertArrayHasKey('test3', $section->getItems());
        $this->assertArrayHasKey('blocktest', $section->getItems());
        $this->assertInstanceOf(Field::class, $section->getItems()['blocktest']);
        $this->assertCount(2, $section->getItems()['blocktest']->getTypes());
    }

    public function testMapFormSectionWithoutLabel()
    {
        $section = new SectionMetadata('sectiontest');

        $form = $this->resourceMetadataMapper->mapForm([$section], 'de');

        /** @var Section $section */
        $section = $form->getItems()['sectiontest'];
        $this->assertEquals('sectiontest', $section->getName());
        $this->assertNull($section->getLabel());
    }

    private function getProperties(string $type): array
    {
        $property1 = new PropertyMetadata('test1');
        $property1->setVisibilityCondition('propertyVisibilityCondition');
        $property1->setSpaceAfter('2');
        $property1->setRequired(false);
        $property1->setType('text_line');
        $property1->setTitles([
            'de' => 'Test 1',
        ]);

        $property2 = new PropertyMetadata('test2');
        $property2->setSize(9);
        $property2->setRequired(true);
        $property2->setType('text_line');
        $property2->setTitles([
            'de' => 'Test 2',
        ]);

        $property3 = new PropertyMetadata('test3');
        $property3->setRequired(true);
        $property3->setType('single_select');
        $property3->setTitles([
            'de' => 'Test 3',
        ]);
        $property3->setDescriptions([
            'de' => 'Description 3',
        ]);
        $property3->setParameters(
            [
                [
                    'name' => 'default_value',
                    'type' => 'string',
                    'meta' => [
                        'title' => [
                            'de' => 'Default value',
                        ],
                        'info_text' => [
                            'de' => 'Description of default value',
                        ],
                    ],
                    'value' => 0,
                ],
                [
                    'name' => 'values',
                    'type' => 'collection',
                    'value' => [
                        [
                            'name' => 'option1',
                            'type' => 'string',
                            'meta' => [
                                'title' => [
                                    'de' => 'Select Option 1',
                                ],
                            ],
                            'value' => 0,
                        ],
                        [
                            'name' => 'option2',
                            'type' => 'string',
                            'meta' => [
                                'title' => [
                                    'de' => 'Select Option 2',
                                ],
                            ],
                            'value' => 1,
                        ],
                    ],
                ],
            ]
        );

        $block = new BlockMetadata('blocktest');
        $block->setVisibilityCondition('blockVisibilityCondition');
        $block->setType('block');
        $block->setTitles([
            'de' => 'Block Test',
        ]);

        $component1 = new ComponentMetadata('type1');
        $component1->setTitles([
            'de' => 'Type 1',
        ]);
        $component1->addChild($property1);
        $component1->addChild($property2);

        $component2 = new ComponentMetadata('type2');
        $component2->setTitles([
            'de' => 'Type 2',
        ]);
        $component2->addChild($property2);

        $block->addComponent($component1);
        $block->addComponent($component2);

        $section = new SectionMetadata('sectiontest');
        $section->setVisibilityCondition('sectionVisibilityCondition');
        $section->setTitles([
            'de' => 'Section Title',
        ]);
        $section->addChild($property1);
        $section->addChild($property2);
        $section->addChild($property3);
        $section->addChild($block);

        switch ($type) {
            case 'properties':
                return [
                    $property1,
                    $property2,
                    $property3,
                ];
            case 'block':
                return [
                    $property1,
                    $block,
                ];
            case 'section':
                return [
                    $section,
                ];
        }
    }
}
