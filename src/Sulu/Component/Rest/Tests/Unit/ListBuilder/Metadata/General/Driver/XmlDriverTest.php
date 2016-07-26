<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Metadata\General\Driver;

use Metadata\ClassMetadata;
use Metadata\Driver\FileLocatorInterface;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\Metadata\General\Driver\XmlDriver;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class XmlDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileLocatorInterface
     */
    private $locator;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    protected function setUp()
    {
        parent::setUp();

        $this->locator = $this->prophesize(FileLocatorInterface::class);
        $this->parameterBag = $this->prophesize(ParameterBagInterface::class);

        $this->parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('SuluContactBundle:Contact');
        $this->parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'SuluContactBundle:Contact.avatar'
        );
        $this->parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'SuluContactBundle:Contact.contactAddresses'
        );
        $this->parameterBag->resolveValue('%sulu.model.contact.class%.tags')->willReturn(
            'SuluContactBundle:Contact.tags'
        );
        $this->parameterBag->resolveValue('%test-parameter%')->willReturn('test-value');
        $this->parameterBag->resolveValue(Argument::any())->willReturnArgument(0);
    }

    public function testLoadMetadataFromFileComplete()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'complete');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(5, $result->propertyMetadata);

        $this->assertEquals(
            ['id', 'firstName', 'lastName', 'avatar', 'fullName'],
            array_keys($result->propertyMetadata)
        );

        $this->assertMetadata(
            [
                'name' => 'id',
                'translation' => 'public.id',
                'type' => 'integer',
            ],
            $result->propertyMetadata['id']
        );
        $this->assertMetadata(
            [
                'name' => 'firstName',
                'translation' => 'contact.contacts.firstName',
                'display' => PropertyMetadata::DISPLAY_ALWAYS,
            ],
            $result->propertyMetadata['firstName']
        );
        $this->assertMetadata(
            [
                'name' => 'lastName',
                'translation' => 'contact.contacts.lastName',
                'display' => PropertyMetadata::DISPLAY_ALWAYS,
            ],
            $result->propertyMetadata['lastName']
        );
        $this->assertMetadata(
            [
                'name' => 'avatar',
                'translation' => 'public.avatar',
                'display' => PropertyMetadata::DISPLAY_ALWAYS,
                'type' => 'thumbnails',
                'sortable' => false,
            ],
            $result->propertyMetadata['avatar']
        );
        $this->assertMetadata(
            [
                'name' => 'fullName',
                'translation' => 'public.name',
                'width' => '100px',
                'minWidth' => '50px',
                'sortable' => false,
                'class' => 'test-class',
            ],
            $result->propertyMetadata['fullName']
        );
    }

    public function testLoadMetadataFromFileEmpty()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'empty');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(0, $result->propertyMetadata);
    }

    public function testLoadMetadataFromFileMinimal()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'minimal');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(3, $result->propertyMetadata);

        $this->assertEquals(['id', 'firstName', 'lastName'], array_keys($result->propertyMetadata));

        $this->assertMetadata(
            [
                'name' => 'id',
                'translation' => 'public.id',
                'type' => 'integer',
            ],
            $result->propertyMetadata['id']
        );
        $this->assertMetadata(
            [
                'name' => 'firstName',
                'translation' => 'contact.contacts.firstName',
                'display' => PropertyMetadata::DISPLAY_ALWAYS,
            ],
            $result->propertyMetadata['firstName']
        );
        $this->assertMetadata(
            [
                'name' => 'lastName',
                'translation' => 'contact.contacts.lastName',
                'display' => PropertyMetadata::DISPLAY_ALWAYS,
            ],
            $result->propertyMetadata['lastName']
        );
    }

    public function testLoadMetadataFromFileInputType()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'filter-type');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(1, $result->propertyMetadata);

        $this->assertEquals(
            ['tags'],
            array_keys($result->propertyMetadata)
        );

        $this->assertMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test-input',
            ],
            $result->propertyMetadata['tags']
        );
    }

    public function testLoadMetadataFromFileParameters()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'filter-type-parameters');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(1, $result->propertyMetadata);

        $this->assertEquals(
            ['tags'],
            array_keys($result->propertyMetadata)
        );

        $this->assertMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test-input',
                'filter-type-parameters' => [
                    'test1' => 'test-value',
                    'test2' => 'test',
                ],
            ],
            $result->propertyMetadata['tags']
        );
    }

    public function testLoadMetadataFromFileNoInputType()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'filter-type-no-input');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(1, $result->propertyMetadata);

        $this->assertEquals(
            ['tags'],
            array_keys($result->propertyMetadata)
        );

        $this->assertMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => null,
                'filter-type-parameters' => [
                    'test1' => 'test-value',
                    'test2' => 'test',
                ],
            ],
            $result->propertyMetadata['tags']
        );
    }

    private function assertMetadata($expected, PropertyMetadata $metadata)
    {
        $expected = array_merge(
            [
                'instance' => PropertyMetadata::class,
                'name' => null,
                'translation' => null,
                'display' => PropertyMetadata::DISPLAY_NO,
                'type' => 'string',
                'width' => '',
                'minWidth' => '',
                'sortable' => true,
                'editable' => false,
                'class' => '',
                'filter-type' => null,
                'filter-type-parameters' => [],
            ],
            $expected
        );

        $this->assertInstanceOf($expected['instance'], $metadata);
        $this->assertEquals($expected['name'], $metadata->getName());
        $this->assertEquals($expected['translation'], $metadata->getTranslation());
        $this->assertEquals($expected['filter-type'], $metadata->getFilterType());
        $this->assertEquals($expected['filter-type-parameters'], $metadata->getFilterTypeParameters());
        $this->assertEquals($expected['display'], $metadata->getDisplay());

        $this->assertEquals($expected['type'], $metadata->getType());
        $this->assertEquals($expected['width'], $metadata->getWidth());
        $this->assertEquals($expected['minWidth'], $metadata->getMinWidth());
        $this->assertEquals($expected['sortable'], $metadata->isSortable());
        $this->assertEquals($expected['editable'], $metadata->isEditable());
        $this->assertEquals($expected['class'], $metadata->getCssClass());
    }

    private function loadMetadataFromFile(XmlDriver $driver, $file)
    {
        $reflectionMethod = new \ReflectionMethod(get_class($driver), 'loadMetadataFromFile');
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs(
            $driver,
            [new \ReflectionClass(new \stdClass()), __DIR__ . '/Resources/' . $file . '.xml']
        );
    }
}
