<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Driver;

use Metadata\ClassMetadata;
use Metadata\Driver\FileLocatorInterface;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\JoinMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\PropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleTypeMetadata;
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
        $this->parameterBag->resolveValue(Argument::any())->willReturnArgument(0);
    }

    public function testLoadMetadataFromFileComplete()
    {
        $driver = new XmlDriver($this->locator->reveal(), $this->parameterBag->reveal());
        $result = $this->loadMetadataFromFile($driver, 'complete');

        $this->assertInstanceOf(ClassMetadata::class, $result);
        $this->assertEquals('stdClass', $result->name);
        $this->assertCount(6, $result->propertyMetadata);

        $this->assertEquals(
            ['id', 'firstName', 'lastName', 'avatar', 'fullName', 'city'],
            array_keys($result->propertyMetadata)
        );

        $this->assertSingleMetadata(
            ['name' => 'id', 'entityName' => 'SuluContactBundle:Contact'],
            $result->propertyMetadata['id']
        );
        $this->assertSingleMetadata(
            ['name' => 'firstName', 'entityName' => 'SuluContactBundle:Contact'],
            $result->propertyMetadata['firstName']
        );
        $this->assertSingleMetadata(
            ['name' => 'lastName', 'entityName' => 'SuluContactBundle:Contact'],
            $result->propertyMetadata['lastName']
        );
        $this->assertSingleMetadata(
            [
                'name' => 'id',
                'entityName' => 'SuluMediaBundle:Media',
                'joins' => [
                    [
                        'entityName' => 'SuluMediaBundle:Media',
                        'entityField' => 'SuluContactBundle:Contact.avatar',
                    ],
                ],
            ],
            $result->propertyMetadata['avatar']
        );

        $this->assertConcatenationType(
            [
                'glue' => ' ',
                'fields' => [
                    [
                        'name' => 'firstName',
                        'entityName' => 'SuluContactBundle:Contact',
                    ],
                    [
                        'name' => 'lastName',
                        'entityName' => 'SuluContactBundle:Contact',
                    ],
                ],
            ],
            $result->propertyMetadata['fullName']
        );

        $this->assertSingleMetadata(
            [
                'name' => 'city',
                'entityName' => 'SuluContactBundle:Address',
                'joins' => [
                    [
                        'entityName' => 'SuluContactBundle:ContactAddress',
                        'entityField' => 'SuluContactBundle:Contact.contactAddresses',
                        'condition' => 'SuluContactBundle:ContactAddress.main = TRUE',
                    ],
                    [
                        'entityName' => 'SuluContactBundle:Address',
                        'entityField' => 'SuluContactBundle:ContactAddress.address',
                    ],
                ],
            ],
            $result->propertyMetadata['city']
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

        $this->assertSingleMetadata(
            ['name' => 'id', 'entityName' => 'SuluContactBundle:Contact'],
            $result->propertyMetadata['id']
        );
        $this->assertSingleMetadata(
            ['name' => 'firstName', 'entityName' => 'SuluContactBundle:Contact'],
            $result->propertyMetadata['firstName']
        );
        $this->assertSingleMetadata(
            ['name' => 'lastName', 'entityName' => 'SuluContactBundle:Contact'],
            $result->propertyMetadata['lastName']
        );
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

    private function assertSingleMetadata(array $expected, PropertyMetadata $metadata)
    {
        $this->assertInstanceOf(SingleTypeMetadata::class, $metadata->getType());
        $this->assertField($expected, $metadata->getType()->getField());
    }

    private function assertField(array $expected, FieldMetadata $metadata)
    {
        $expected = array_merge(
            [
                'name' => null,
                'entityName' => null,
                'joins' => [],
            ],
            $expected
        );

        $this->assertEquals($expected['name'], $metadata->getName());
        $this->assertEquals($expected['entityName'], $metadata->getEntityName());
        $this->assertCount(count($expected['joins']), $metadata->getJoins());

        $i = 0;
        foreach ($expected['joins'] as $joinExpected) {
            $this->assertJoin($joinExpected, $metadata->getJoins()[$i]);
            ++$i;
        }
    }

    private function assertJoin(array $expected, JoinMetadata $metadata)
    {
        $expected = array_merge(
            [
                'entityName' => null,
                'entityField' => null,
                'condition' => null,
                'conditionMethod' => 'WITH',
                'method' => 'LEFT',
            ],
            $expected
        );

        $this->assertEquals($expected['entityName'], $metadata->getEntityName());
        $this->assertEquals($expected['entityField'], $metadata->getEntityField());
        $this->assertEquals($expected['condition'], $metadata->getCondition());
        $this->assertEquals($expected['conditionMethod'], $metadata->getConditionMethod());
        $this->assertEquals($expected['method'], $metadata->getMethod());
    }

    private function assertConcatenationType($expected, PropertyMetadata $metadata)
    {
        $expected = array_merge(
            [
                'glue' => null,
                'fields' => [],
            ],
            $expected
        );

        $this->assertInstanceOf(ConcatenationTypeMetadata::class, $metadata->getType());

        $this->assertEquals($expected['glue'], $metadata->getType()->getGlue());
        $this->assertCount(count($expected['fields']), $metadata->getType()->getFields());

        $i = 0;
        foreach ($expected['fields'] as $fieldExpected) {
            $this->assertField($fieldExpected, $metadata->getType()->getFields()[$i]);
            ++$i;
        }
    }
}
