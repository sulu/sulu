<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\FormMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Translation\TranslatorInterface;

class FormXmlLoaderTest extends TestCase
{
    /**
     * @var FormXmlLoader
     */
    private $loader;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function setUp()
    {
        $this->expressionLanguage = $this->prophesize(ExpressionLanguage::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $propertiesXmlParser = new PropertiesXmlParser(
            $this->expressionLanguage->reveal(),
            $this->translator->reveal(),
            ['en' => 'en', 'de' => 'de', 'fr' => 'fr', 'nl' => 'nl']
        );
        $schemaXmlParser = new SchemaXmlParser();
        $this->loader = new FormXmlLoader($propertiesXmlParser, $schemaXmlParser);
    }

    public function testLoadForm()
    {
        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form.xml'
        );

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(4, $formMetadata->getProperties());

        $this->assertEquals('formOfAddress', $formMetadata->getProperties()['formOfAddress']->getName());
        $this->assertEquals(
            'default_value',
            $formMetadata->getProperties()['formOfAddress']->getParameter(0)['name']
        );
        $this->assertSame(0, $formMetadata->getProperties()['formOfAddress']->getParameter(0)['value']);
        $this->assertEquals('firstName', $formMetadata->getProperties()['firstName']->getName());
        $this->assertEquals('lastName', $formMetadata->getProperties()['lastName']->getName());
        $this->assertEquals('salutation', $formMetadata->getProperties()['salutation']->getName());

        $this->assertNull($formMetadata->getSchema());
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

        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_with_localizations.xml'
        );

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);
        $this->assertCount(4, $formMetadata->getProperties());

        $this->assertEquals('en_form_of_address', $formMetadata->getProperties()['formOfAddress']->getTitle('en'));
        $this->assertEquals('de_form_of_address', $formMetadata->getProperties()['formOfAddress']->getTitle('de'));
        $this->assertEquals('fr_form_of_address', $formMetadata->getProperties()['formOfAddress']->getTitle('fr'));
        $this->assertEquals('nl_form_of_address', $formMetadata->getProperties()['formOfAddress']->getTitle('nl'));
        $this->assertEquals('en_first_name', $formMetadata->getProperties()['firstName']->getTitle('en'));
        $this->assertEquals('de_first_name', $formMetadata->getProperties()['firstName']->getTitle('de'));
        $this->assertEquals('fr_first_name', $formMetadata->getProperties()['firstName']->getTitle('fr'));
        $this->assertEquals('nl_first_name', $formMetadata->getProperties()['firstName']->getTitle('nl'));
        $this->assertEquals('en_last_name', $formMetadata->getProperties()['lastName']->getTitle('en'));
        $this->assertEquals('Deutscher Nachname', $formMetadata->getProperties()['lastName']->getTitle('de'));
        $this->assertEquals('fr_last_name', $formMetadata->getProperties()['lastName']->getTitle('fr'));
        $this->assertEquals('nl_last_name', $formMetadata->getProperties()['lastName']->getTitle('nl'));
        $this->assertEquals('en_salutation', $formMetadata->getProperties()['salutation']->getTitle('en'));
        $this->assertEquals('de_salutation', $formMetadata->getProperties()['salutation']->getTitle('de'));
        $this->assertEquals('fr_salutation', $formMetadata->getProperties()['salutation']->getTitle('fr'));
        $this->assertEquals('nl_salutation', $formMetadata->getProperties()['salutation']->getTitle('nl'));

        $this->assertEquals(
            [
                'en' => 'en_mr',
                'de' => 'de_mr',
                'fr' => 'fr_mr',
                'nl' => 'nl_mr',
            ],
            $formMetadata->getProperties()['formOfAddress']->getParameters()[1]['value'][0]['meta']['title']
        );
        $this->assertEquals(
            [
                'en' => 'en_ms',
                'de' => 'de_ms',
                'fr' => 'fr_ms',
                'nl' => 'nl_ms',
            ],
            $formMetadata->getProperties()['formOfAddress']->getParameters()[1]['value'][1]['meta']['title']
        );

        $this->assertNull($formMetadata->getSchema());
    }

    public function testLoadFormWithEvaluations()
    {
        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_with_evaluations.xml'
        );

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(5, $formMetadata->getProperties());

        $this->assertEquals(
            'lastName == \'section_property\'',
            $formMetadata->getProperties()['formOfAddress']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'section_property\'',
            $formMetadata->getProperties()['formOfAddress']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block\'',
            $formMetadata->getProperties()['block']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block\'',
            $formMetadata->getProperties()['block']->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'block_property\'',
            $formMetadata->getProperties()['block']->getComponents()[0]->getChild('name')->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'block_property\'',
            $formMetadata->getProperties()['block']->getComponents()[0]->getChild('name')->getVisibleCondition()
        );

        $this->assertEquals(
            'lastName == \'property\'',
            $formMetadata->getProperties()['salutation']->getDisabledCondition()
        );
        $this->assertEquals(
            'firstName == \'property\'',
            $formMetadata->getProperties()['salutation']->getVisibleCondition()
        );
    }

    public function testLoadFormWithSchema()
    {
        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_with_schema.xml'
        );

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(2, $formMetadata->getProperties());

        $this->assertEquals('first', $formMetadata->getProperties()['first']->getName());
        $this->assertEquals('second', $formMetadata->getProperties()['second']->getName());

        $this->assertEquals(
            [
                'anyOf' => [
                    [
                        'properties' => [
                            'first' => [
                                'name' => 'first',
                                'const' => 1,
                            ],
                        ],
                    ],
                    [
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
                        'properties' => [
                            'first' => [
                                'name' => 'first',
                                'const' => 1,
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
        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_without_label.xml'
        );

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);
    }

    public function testLoadFormWithExpressionParam()
    {
        $this->expressionLanguage->evaluate('service(\'test\').getId()')->willReturn(5);
        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_with_expression_param.xml'
        );

        $this->assertEquals(5, $formMetadata->getProperties()['name']->getParameters()[0]['value']);
    }

    public function testLoadFormWithSizedSections()
    {
        /** @var FormMetadata $formMetadata */
        $formMetadata = $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_with_sections.xml'
        );

        $this->assertInstanceOf(FormMetadata::class, $formMetadata);

        $this->assertCount(2, $formMetadata->getChildren());
        $this->assertEquals('logo', $formMetadata->getChildren()['logo']->getName());
        $this->assertEquals(4, $formMetadata->getChildren()['logo']->getSize());
        $this->assertCount(1, $formMetadata->getChildren()['logo']->getChildren());
        $this->assertEquals('name', $formMetadata->getChildren()['name']->getName());
        $this->assertEquals(8, $formMetadata->getChildren()['name']->getSize());
        $this->assertCount(1, $formMetadata->getChildren()['name']->getChildren());
    }

    public function testLoadFormInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->loader->load(
            __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'form_invalid.xml'
        );
    }
}
