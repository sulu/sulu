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

    public function setUp()
    {
        $this->expressionLanguage = $this->prophesize(ExpressionLanguage::class);
        $propertiesXmlParser = new PropertiesXmlParser($this->expressionLanguage->reveal());
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
