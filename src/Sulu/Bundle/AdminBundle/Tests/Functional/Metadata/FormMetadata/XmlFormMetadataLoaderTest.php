<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;

class XmlFormMetadataLoaderTest extends KernelTestCase
{
    /**
     * @var XmlFormMetadataLoader
     */
    private $xmlFormMetadataLoader;

    public function setUp(): void
    {
        $this->xmlFormMetadataLoader = $this->getContainer()->get('sulu_admin_test.xml_form_metadata_loader');
    }

    public function testGetMetadataWithOnInvalid(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_with_on_invalid', 'en');
        $this->assertCount(1, $form->getItems());
        $this->assertEquals(
            'ignore',
            $form->getItems()['audience_targeting_groups']->getOnInvalid()
        );
    }

    public function testGetMetadataWithSchema(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_with_schema', 'en');
        $schema = $form->getSchema()->toJsonSchema();
        $this->assertCount(1, \array_keys($schema));
        $this->assertCount(2, $schema['allOf']);
        $this->assertEquals(['first', 'third'], $schema['allOf'][0]['required']);
    }

    public function testGetMetadataWithEvaluations(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_with_evaluations', 'en');

        $this->assertEquals(
            'lastName == \'section_property\'',
            $form->getItems()['highlight']->getItems()['formOfAddress']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'section_property\'',
            $form->getItems()['highlight']->getItems()['formOfAddress']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block\'',
            $form->getItems()['block']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block\'',
            $form->getItems()['block']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block_property\'',
            $form->getItems()['block']->getTypes()['test']->getItems()['name']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block_property\'',
            $form->getItems()['block']->getTypes()['test']->getItems()['name']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'property\'',
            $form->getItems()['salutation']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'property\'',
            $form->getItems()['salutation']->getVisibleCondition()
        );
    }

    public function testGetMetadataWithExpressionParam(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_with_expression_param', 'en');
        $this->assertNotNull($form);
        $this->assertContains('name', \array_keys($form->getItems()));
        $this->assertEquals('expression', $form->getItems()['name']->getOptions()['id']->getType());
        $this->assertEquals('service(\'test\').getId()', $form->getItems()['name']->getOptions()['id']->getValue());
    }

    public function testGetMetadataWithoutLabel(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_without_label', 'en');
        $this->assertNull($form->getItems()['name']->getLabel('en'));
    }

    public function testGetMetadataFromMultipleFiles(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('merged_form', 'en');
        $this->assertContains('field1', \array_keys($form->getItems()));
        $this->assertContains('field2', \array_keys($form->getItems()));

        $schema = $form->getSchema()->toJsonSchema();
        $this->assertCount(2, $schema['allOf']);
    }

    public function testGetMetadataWithNestedSections(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_with_nested_sections', 'en');

        $section1 = $form->getItems()['test1'];
        $section2 = $form->getItems()['test2'];
        $section22 = $section2->getItems()['test22'];

        $this->assertInstanceOf(SectionMetadata::class, $section1);
        $this->assertInstanceOf(SectionMetadata::class, $section2);
        $this->assertInstanceOf(SectionMetadata::class, $section22);

        $this->assertEquals('test11', $section1->getItems()['test11']->getName());
        $this->assertEquals('test21', $section2->getItems()['test21']->getName());
        $this->assertEquals('test221', $section22->getItems()['test221']->getName());
    }

    public function testGetMetadataWithBlocks(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_with_blocks', 'en');

        $blocks = $form->getItems()['blocks'];

        $this->assertInstanceOf(FieldMetadata::class, $blocks);
        $this->assertEquals('editor', $blocks->getDefaultType());

        $options = $blocks->getOptions();
        $this->assertCount(1, $options);
        $this->assertEquals('settings_form_key', $options['settings_form_key']->getName());
        $this->assertEquals('test_block_settings', $options['settings_form_key']->getValue());

        $types = $blocks->getTypes();

        $this->assertCount(3, $types);
        $this->assertEquals('editor', $types['editor']->getKey());
        $this->assertEquals('editor_image', $types['editor_image']->getKey());
        $this->assertEquals('text_block', $types['text_block']->getKey());
    }

    public function testGetMetadataWithPropertyWithTypes(): void
    {
        $form = $this->xmlFormMetadataLoader->getMetadata('form_with_property_with_types', 'en');

        /** @var FieldMetadata $image */
        $image = $form->getItems()['image'];

        $this->assertInstanceOf(FieldMetadata::class, $image);
        $this->assertEquals('editor', $image->getDefaultType());

        $types = $image->getTypes();
        $this->assertCount(2, $types);
        $this->assertEquals('editor', $types['editor']->getKey());
        $this->assertCount(1, $types['editor']->getItems());
        $this->assertEquals('article', $types['editor']->getItems()['article']->getName());
        $this->assertEquals('editor_image', $types['editor_image']->getKey());
        $this->assertCount(2, $types['editor_image']->getItems());
        $this->assertEquals('images', $types['editor_image']->getItems()['images']->getName());
        $this->assertEquals('article', $types['editor_image']->getItems()['article']->getName());
    }
}
