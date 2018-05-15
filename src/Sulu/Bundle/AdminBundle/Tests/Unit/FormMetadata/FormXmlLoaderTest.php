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

class FormXmlLoaderTest extends TestCase
{
    /**
     * @var FormXmlLoader
     */
    private $loader;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    public function setUp()
    {
        $propertiesXmlParser = new PropertiesXmlParser();

        $this->loader = new FormXmlLoader($propertiesXmlParser);
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
        $this->assertEquals('firstName', $formMetadata->getProperties()['firstName']->getName());
        $this->assertEquals('lastName', $formMetadata->getProperties()['lastName']->getName());
        $this->assertEquals('salutation', $formMetadata->getProperties()['salutation']->getName());
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
