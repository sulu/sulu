<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\General\Driver;

use Metadata\ClassMetadata;
use Metadata\Driver\FileLocatorInterface;
use Prophecy\Argument;
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
    }

    public function testLoadMetadataFromFileComplete()
    {
        $driver = new XmlDriver($this->locator->reveal());
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
                'disabled' => true,
                'type' => 'integer',
            ],
            $result->propertyMetadata['id']
        );
        $this->assertMetadata(
            [
                'name' => 'firstName',
                'translation' => 'contact.contacts.firstName',
                'default' => true,
            ],
            $result->propertyMetadata['firstName']
        );
        $this->assertMetadata(
            [
                'name' => 'lastName',
                'translation' => 'contact.contacts.lastName',
                'default' => true,
            ],
            $result->propertyMetadata['lastName']
        );
        $this->assertMetadata(
            [
                'name' => 'avatar',
                'translation' => 'public.avatar',
                'default' => true,
                'type' => 'thumbnails',
                'sortable' => false,
            ],
            $result->propertyMetadata['avatar']
        );
        $this->assertMetadata(
            [
                'name' => 'fullName',
                'translation' => 'public.name',
                'disabled' => true,
                'width' => '100px',
                'minWidth' => '50px',
                'sortable' => false,
                'class' => 'test-class'
            ],
            $result->propertyMetadata['fullName']
        );
    }

    public function testLoadMetadataFromFileEmpty()
    {
        $driver = new XmlDriver($this->locator->reveal());
        $result = $this->loadMetadataFromFile($driver, 'empty');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(0, $result->propertyMetadata);
    }

    public function testLoadMetadataFromFileMinimal()
    {
        $driver = new XmlDriver($this->locator->reveal());
        $result = $this->loadMetadataFromFile($driver, 'minimal');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(3, $result->propertyMetadata);

        $this->assertEquals(['id', 'firstName', 'lastName'], array_keys($result->propertyMetadata));

        $this->assertMetadata(
            [
                'name' => 'id',
                'translation' => 'public.id',
                'disabled' => true,
                'type' => 'integer',
            ],
            $result->propertyMetadata['id']
        );
        $this->assertMetadata(
            [
                'name' => 'firstName',
                'translation' => 'contact.contacts.firstName',
                'default' => true,
            ],
            $result->propertyMetadata['firstName']
        );
        $this->assertMetadata(
            [
                'name' => 'lastName',
                'translation' => 'contact.contacts.lastName',
                'default' => true,
            ],
            $result->propertyMetadata['lastName']
        );
    }

    private function assertMetadata($expected, PropertyMetadata $metadata)
    {
        $expected = array_merge(
            [
                'instance' => PropertyMetadata::class,
                'name' => null,
                'translation' => null,
                'disabled' => false,
                'default' => false,
                'type' => 'string',
                'width' => '',
                'minWidth' => '',
                'sortable' => true,
                'editable' => false,
                'class' => '',
            ],
            $expected
        );

        $this->assertInstanceOf($expected['instance'], $metadata);
        $this->assertEquals($expected['name'], $metadata->getName());
        $this->assertEquals($expected['translation'], $metadata->getTranslation());
        $this->assertEquals($expected['disabled'], $metadata->isDisabled());
        $this->assertEquals($expected['default'], $metadata->isDefault());
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
