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

    private function getProperties(string $type): array
    {
        $property1 = new PropertyMetadata('test1');
        $property1->setDisabledCondition('propertyDisabledCondition');
        $property1->setVisibleCondition('propertyVisibleCondition');
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
        $block->setDisabledCondition('blockDisabledCondition');
        $block->setVisibleCondition('blockVisibleCondition');
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
        $component2->addChild($property2);

        $block->addComponent($component1);
        $block->addComponent($component2);

        $section = new SectionMetadata('sectiontest');
        $section->setDisabledCondition('sectionDisabledCondition');
        $section->setVisibleCondition('sectionVisibleCondition');
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
