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
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class XmlDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadataFromFileComplete()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $parameterBag = $this->prophesize(ParameterBagInterface::class);

        $parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('SuluContactBundle:Contact');
        $parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'SuluContactBundle:Contact.avatar'
        );
        $parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'SuluContactBundle:Contact.contactAddresses'
        );
        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $driver = new XmlDriver($locator->reveal(), $parameterBag->reveal());

        $reflectionMethod = new \ReflectionMethod(get_class($driver), 'loadMetadataFromFile');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs(
            $driver,
            [new \ReflectionClass(new \stdClass()), __DIR__ . '/Resources/complete.xml']
        );

        self::assertInstanceOf(ClassMetadata::class, $result);
        self::assertEquals('stdClass', $result->name);
        self::assertCount(6, $result->propertyMetadata);

        self::assertEquals(
            ['id', 'firstName', 'lastName', 'avatar', 'fullName', 'city'],
            array_keys($result->propertyMetadata)
        );

        self::assertEquals('id', $result->propertyMetadata['id']->getType()->getField()->getName());
        self::assertEquals(
            'SuluContactBundle:Contact',
            $result->propertyMetadata['id']->getType()->getField()->getEntityName()
        );
        self::assertEmpty($result->propertyMetadata['id']->getType()->getField()->getJoins());

        self::assertEquals('firstName', $result->propertyMetadata['firstName']->getType()->getField()->getName());
        self::assertEquals(
            'SuluContactBundle:Contact',
            $result->propertyMetadata['firstName']->getType()->getField()->getEntityName()
        );
        self::assertEmpty($result->propertyMetadata['id']->getType()->getField()->getJoins());

        self::assertEquals('lastName', $result->propertyMetadata['lastName']->getType()->getField()->getName());
        self::assertEquals(
            'SuluContactBundle:Contact',
            $result->propertyMetadata['lastName']->getType()->getField()->getEntityName()
        );
        self::assertEmpty($result->propertyMetadata['lastName']->getType()->getField()->getJoins());

        self::assertEquals('id', $result->propertyMetadata['avatar']->getType()->getField()->getName());
        self::assertEquals(
            'SuluMediaBundle:Media',
            $result->propertyMetadata['avatar']->getType()->getField()->getEntityName()
        );
        self::assertCount(1, $result->propertyMetadata['avatar']->getType()->getField()->getJoins());
        $join = $result->propertyMetadata['avatar']->getType()->getField()->getJoins()[0];
        self::assertEquals('SuluMediaBundle:Media', $join->getEntityName());
        self::assertEquals('SuluContactBundle:Contact.avatar', $join->getEntityField());
        self::assertNull($join->getCondition());
        self::assertEquals('WITH', $join->getConditionMethod());
        self::assertEquals('LEFT', $join->getMethod());

        self::assertInstanceOf(ConcatenationType::class, $result->propertyMetadata['fullName']->getType());
        self::assertEquals(' ', $result->propertyMetadata['fullName']->getType()->getGlue());
        self::assertCount(2, $result->propertyMetadata['fullName']->getType()->getFields());

        $field = $result->propertyMetadata['fullName']->getType()->getFields()[0];
        self::assertEquals('firstName', $field->getName());
        self::assertEquals(
            'SuluContactBundle:Contact',
            $field->getEntityName()
        );
        self::assertEmpty($field->getJoins());

        $field = $result->propertyMetadata['fullName']->getType()->getFields()[1];
        self::assertEquals('lastName', $field->getName());
        self::assertEquals(
            'SuluContactBundle:Contact',
            $field->getEntityName()
        );
        self::assertEmpty($field->getJoins());

        self::assertEquals('city', $result->propertyMetadata['city']->getType()->getField()->getName());
        self::assertEquals(
            'SuluContactBundle:Address',
            $result->propertyMetadata['city']->getType()->getField()->getEntityName()
        );
        self::assertCount(2, $result->propertyMetadata['city']->getType()->getField()->getJoins());

        $join = $result->propertyMetadata['city']->getType()->getField()->getJoins()[0];
        self::assertEquals('SuluContactBundle:ContactAddress', $join->getEntityName());
        self::assertEquals('SuluContactBundle:Contact.contactAddresses', $join->getEntityField());
        self::assertEquals('SuluContactBundle:ContactAddress.main = TRUE', $join->getCondition());
        self::assertEquals('WITH', $join->getConditionMethod());
        self::assertEquals('LEFT', $join->getMethod());

        $join = $result->propertyMetadata['city']->getType()->getField()->getJoins()[1];
        self::assertEquals('SuluContactBundle:Address', $join->getEntityName());
        self::assertEquals('SuluContactBundle:ContactAddress.address', $join->getEntityField());
        self::assertNull($join->getCondition());
        self::assertEquals('WITH', $join->getConditionMethod());
        self::assertEquals('LEFT', $join->getMethod());
    }

    public function testLoadMetadataFromFileEmpty()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $parameterBag = $this->prophesize(ParameterBagInterface::class);

        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $driver = new XmlDriver($locator->reveal(), $parameterBag->reveal());

        $reflectionMethod = new \ReflectionMethod(get_class($driver), 'loadMetadataFromFile');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs(
            $driver,
            [new \ReflectionClass(new \stdClass()), __DIR__ . '/Resources/empty.xml']
        );

        self::assertInstanceOf(ClassMetadata::class, $result);
        self::assertEquals('stdClass', $result->name);
        self::assertCount(0, $result->propertyMetadata);
    }

    public function testLoadMetadataFromFileMinimal()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $parameterBag = $this->prophesize(ParameterBagInterface::class);

        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $driver = new XmlDriver($locator->reveal(), $parameterBag->reveal());

        $reflectionMethod = new \ReflectionMethod(get_class($driver), 'loadMetadataFromFile');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs(
            $driver,
            [new \ReflectionClass(new \stdClass()), __DIR__ . '/Resources/minimal.xml']
        );

        self::assertInstanceOf(ClassMetadata::class, $result);
        self::assertEquals('stdClass', $result->name);
        self::assertCount(3, $result->propertyMetadata);

        self::assertEquals(['id', 'firstName', 'lastName'], array_keys($result->propertyMetadata));

        self::assertEquals('id', $result->propertyMetadata['id']->getType()->getField()->getName());
        self::assertEquals(
            '%sulu.model.contact.class%',
            $result->propertyMetadata['id']->getType()->getField()->getEntityName()
        );

        self::assertEquals('firstName', $result->propertyMetadata['firstName']->getType()->getField()->getName());
        self::assertEquals(
            '%sulu.model.contact.class%',
            $result->propertyMetadata['firstName']->getType()->getField()->getEntityName()
        );

        self::assertEquals('lastName', $result->propertyMetadata['lastName']->getType()->getField()->getName());
        self::assertEquals(
            '%sulu.model.contact.class%',
            $result->propertyMetadata['lastName']->getType()->getField()->getEntityName()
        );
    }
}
