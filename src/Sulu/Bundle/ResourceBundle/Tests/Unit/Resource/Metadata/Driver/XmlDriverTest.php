<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Tests\Unit\Resource\Metadata\Driver;

use Metadata\ClassMetadata;
use Metadata\Driver\FileLocatorInterface;
use Prophecy\Argument;
use Sulu\Bundle\ResourceBundle\Resource\Metadata\Driver\XmlDriver;
use Sulu\Bundle\ResourceBundle\Resource\Metadata\PropertyMetadata;
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
        $this->parameterBag->resolveValue('%test-parameter%')->willReturn(
            'test-value'
        );
        $this->parameterBag->resolveValue(Argument::any())->willReturnArgument(0);
    }

    public function testLoadMetadataFromFileInputType()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'input-type');

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
                'input-type' => 'test-input',
            ],
            $result->propertyMetadata['tags']
        );
    }

    public function testLoadMetadataFromFileParameters()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'parameters');

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
                'input-type' => 'test-input',
                'parameters' => [
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
        $result = $this->loadMetadataFromFile($driver, 'no-input-type');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertEmpty($result->propertyMetadata);
    }

    private function assertMetadata($expected, PropertyMetadata $metadata)
    {
        $expected = array_merge(
            [
                'instance' => PropertyMetadata::class,
                'name' => null,
                'input-type' => null,
                'parameters' => [],
            ],
            $expected
        );

        $this->assertInstanceOf($expected['instance'], $metadata);
        $this->assertEquals($expected['name'], $metadata->getName());
        $this->assertEquals($expected['input-type'], $metadata->getInputType());
        $this->assertEquals($expected['parameters'], $metadata->getParameters());
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
