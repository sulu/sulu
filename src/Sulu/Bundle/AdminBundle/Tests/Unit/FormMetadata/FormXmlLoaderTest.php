<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\FormMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\LocalizedFormMetadataCollection;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Symfony\Component\Translation\TranslatorInterface;

class FormXmlLoaderTest extends TestCase
{
    /**
     * @var FormXmlLoader
     */
    private $loader;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $propertiesXmlParser = new PropertiesXmlParser(
            $this->translator->reveal(),
            ['en' => 'en', 'de' => 'de', 'fr' => 'fr', 'nl' => 'nl']
        );
        $schemaXmlParser = new SchemaXmlParser();
        $locales = ['de', 'en'];
        $formMetadataMapper = new FormMetadataMapper();
        $this->loader = new FormXmlLoader($propertiesXmlParser, $schemaXmlParser, $locales, $formMetadataMapper);
    }

    public function testLoadForm()
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form.xml');

        $this->assertInstanceOf(LocalizedFormMetadataCollection::class, $formMetadataCollection);

        $this->assertCount(2, $formMetadataCollection->getItems());

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertEquals('form', $formMetadata->getKey());
        $this->assertCount(4, $formMetadata->getItems());

        $this->assertEquals('formOfAddress', $formMetadata->getItems()['formOfAddress']->getName());
        $this->assertEquals(
            'default_value',
            $formMetadata->getItems()['formOfAddress']->getOptions()['default_value']->getName()
        );
        $this->assertSame(0, $formMetadata->getItems()['formOfAddress']->getOptions()['default_value']->getValue());
        $this->assertSame(
            0,
            ($formMetadata->getItems()['formOfAddress']->getOptions()['values']->getValue()[0]->getName())
        );
        $this->assertSame(
            1,
            ($formMetadata->getItems()['formOfAddress']->getOptions()['values']->getValue()[1]->getName())
        );
        $this->assertEquals('firstName', $formMetadata->getItems()['firstName']->getName());
        $this->assertEquals('lastName', $formMetadata->getItems()['lastName']->getName());
        $this->assertEquals('salutation', $formMetadata->getItems()['salutation']->getName());

        $schemaMetadata = $formMetadata->getSchema();
        $this->assertInstanceOf(SchemaMetadata::class, $schemaMetadata);
        $this->assertCount(3, $schemaMetadata->toJsonSchema()['required']);
    }

    public function testLoadFormWithLocalization()
    {
        $this->translator->trans('mr', [], 'admin', 'en')->willReturn('en_mr');
        $this->translator->trans('mr', [], 'admin', 'de')->willReturn('de_mr');
        $this->translator->trans('mr', [], 'admin', 'fr')->willReturn('fr_mr');
        $this->translator->trans('mr', [], 'admin', 'nl')->willReturn('nl_mr');

        $this->translator->trans('ms', [], 'admin', 'en')->willReturn('en_ms');
        $this->translator->trans('ms', [], 'admin', 'de')->willReturn('de_ms');
        $this->translator->trans('ms', [], 'admin', 'fr')->willReturn('fr_ms');
        $this->translator->trans('ms', [], 'admin', 'nl')->willReturn('nl_ms');

        $this->translator->trans('form_of_address', [], 'admin', 'en')->willReturn('en_form_of_address');
        $this->translator->trans('form_of_address', [], 'admin', 'de')->willReturn('de_form_of_address');
        $this->translator->trans('form_of_address', [], 'admin', 'fr')->willReturn('fr_form_of_address');
        $this->translator->trans('form_of_address', [], 'admin', 'nl')->willReturn('nl_form_of_address');

        $this->translator->trans('first_name', [], 'admin', 'en')->willReturn('en_first_name');
        $this->translator->trans('first_name', [], 'admin', 'de')->willReturn('de_first_name');
        $this->translator->trans('first_name', [], 'admin', 'fr')->willReturn('fr_first_name');
        $this->translator->trans('first_name', [], 'admin', 'nl')->willReturn('nl_first_name');

        $this->translator->trans('last_name', [], 'admin', 'en')->willReturn('en_last_name');
        $this->translator->trans('last_name', [], 'admin', 'de')->willReturn('de_last_name');
        $this->translator->trans('last_name', [], 'admin', 'fr')->willReturn('fr_last_name');
        $this->translator->trans('last_name', [], 'admin', 'nl')->willReturn('nl_last_name');

        $this->translator->trans('salutation', [], 'admin', 'en')->willReturn('en_salutation');
        $this->translator->trans('salutation', [], 'admin', 'de')->willReturn('de_salutation');
        $this->translator->trans('salutation', [], 'admin', 'fr')->willReturn('fr_salutation');
        $this->translator->trans('salutation', [], 'admin', 'nl')->willReturn('nl_salutation');

        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_localizations.xml');

        $formMetadataEn = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadataEn);
        $this->assertCount(4, $formMetadataEn->getItems());
        $this->assertEquals('en_form_of_address', $formMetadataEn->getItems()['formOfAddress']->getLabel());
        $this->assertEquals('en_first_name', $formMetadataEn->getItems()['firstName']->getLabel());
        $this->assertEquals('en_last_name', $formMetadataEn->getItems()['lastName']->getLabel());
        $this->assertEquals('en_salutation', $formMetadataEn->getItems()['salutation']->getLabel());
        $this->assertEquals('en_mr', $formMetadataEn->getItems()['formOfAddress']->getOptions()['values']
            ->getValue()[0]->getTitle());
        $this->assertEquals('en_ms', $formMetadataEn->getItems()['formOfAddress']->getOptions()['values']
            ->getValue()[1]->getTitle());

        $formMetadataDe = $formMetadataCollection->get('de');
        $this->assertCount(4, $formMetadataDe->getItems());
        $this->assertEquals('de_form_of_address', $formMetadataDe->getItems()['formOfAddress']->getLabel());
        $this->assertEquals('de_first_name', $formMetadataDe->getItems()['firstName']->getLabel());
        $this->assertEquals('Deutscher Nachname', $formMetadataDe->getItems()['lastName']->getLabel());
        $this->assertEquals('de_salutation', $formMetadataDe->getItems()['salutation']->getLabel());
        $this->assertEquals('de_mr', $formMetadataDe->getItems()['formOfAddress']->getOptions()['values']->getValue()[0]->getTitle());
        $this->assertEquals('de_ms', $formMetadataDe->getItems()['formOfAddress']->getOptions()['values']->getValue()[1]->getTitle());

        $schemaMetadataEn = $formMetadataEn->getSchema();
        $this->assertInstanceOf(SchemaMetadata::class, $schemaMetadataEn);
        $this->assertCount(3, $schemaMetadataEn->toJsonSchema()['required']);

        $schemaMetadataDe = $formMetadataDe->getSchema();
        $this->assertInstanceOf(SchemaMetadata::class, $schemaMetadataDe);
        $this->assertCount(3, $schemaMetadataDe->toJsonSchema()['required']);
    }

    public function testLoadFormWithEvaluations()
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_evaluations.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(5, $formMetadata->getItems());

        $this->assertEquals(
            'lastName == \'section_property\'',
            $formMetadata->getItems()['highlight']->getItems()['formOfAddress']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'section_property\'',
            $formMetadata->getItems()['highlight']->getItems()['formOfAddress']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block\'',
            $formMetadata->getItems()['block']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block\'',
            $formMetadata->getItems()['block']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block_property\'',
            $formMetadata->getItems()['block']->getTypes()['test']->getItems()['name']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block_property\'',
            $formMetadata->getItems()['block']->getTypes()['test']->getItems()['name']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'property\'',
            $formMetadata->getItems()['salutation']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'property\'',
            $formMetadata->getItems()['salutation']->getVisibleCondition()
        );
    }

    public function testLoadFormWithSchema()
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_schema.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(3, $formMetadata->getItems());

        $this->assertEquals('first', $formMetadata->getItems()['first']->getName());
        $this->assertEquals('second', $formMetadata->getItems()['second']->getName());

        $this->assertEquals(
            [
                'required' => [],
                'allOf' => [
                    [
                        'required' => [
                            'first',
                            'third',
                        ],
                    ],
                    [
                        'required' => [],
                        'anyOf' => [
                            [
                                'required' => [],
                                'properties' => [
                                    'first' => [
                                        'name' => 'first',
                                        'const' => 1,
                                    ],
                                ],
                            ],
                            [
                                'required' => [],
                                'properties' => [
                                    'second' => [
                                        'name' => 'second',
                                        'const' => 2,
                                    ],
                                ],
                            ],
                        ],
                        'allOf' => [
                            [
                                'required' => [],
                                'properties' => [
                                    'first' => [
                                        'name' => 'first',
                                        'const' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $formMetadata->getSchema()->toJsonSchema()
        );
    }

    public function testLoadFormWithoutLabel()
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_without_label.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);
    }

    public function testLoadFormWithExpressionParam()
    {
        /**
         * LocalizedFormMetadataCollection.
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_expression_param.xml');

        $formMetadata = $formMetadataCollection->get('en');

        $this->assertEquals(
            'service(\'test\').getId()',
            $formMetadata->getItems()['name']->getOptions()['id']->getValue()
        );

        $this->assertEquals(
            'expression',
            $formMetadata->getItems()['name']->getOptions()['id']->getType()
        );
    }

    public function testLoadFormWithSizedSections()
    {
        /**
         * @var LocalizedFormMetadataCollection
         */
        $formMetadataCollection = $this->loader->load($this->getFormDirectory() . 'form_with_sections.xml');

        $formMetadata = $formMetadataCollection->get('en');
        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(2, $formMetadata->getItems());
        $this->assertEquals('logo', $formMetadata->getItems()['logo']->getName());
        $this->assertEquals(4, $formMetadata->getItems()['logo']->getColSpan());
        $this->assertCount(1, $formMetadata->getItems()['logo']->getItems());
        $this->assertEquals('name', $formMetadata->getItems()['name']->getName());
        $this->assertEquals(8, $formMetadata->getItems()['name']->getColSpan());
        $this->assertCount(1, $formMetadata->getItems()['name']->getItems());
    }

    public function testLoadFormInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_invalid.xml'
        );
    }

    private function getFormDirectory()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
            . 'Application' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR;
    }
}
