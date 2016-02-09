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
    public function testLoadMetadataFromFileComplete()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $parameterBag = $this->prophesize(ParameterBagInterface::class);

        $driver = new XmlDriver($locator->reveal(), $parameterBag->reveal());

        $reflectionMethod = new \ReflectionMethod(get_class($driver), 'loadMetadataFromFile');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs(
            $driver,
            [new \ReflectionClass(new \stdClass()), __DIR__ . '/Resources/complete.xml']
        );

        self::assertInstanceOf(ClassMetadata::class, $result);
        self::assertEquals('stdClass', $result->name);
        self::assertCount(5, $result->propertyMetadata);

        self::assertEquals(
            ['id', 'firstName', 'lastName', 'avatar', 'fullName'],
            array_keys($result->propertyMetadata)
        );

        $this->metadataTest($result->propertyMetadata['id'], 'public.id', true, false, 'integer');
        $this->metadataTest($result->propertyMetadata['firstName'], 'contact.contacts.firstName', false, true);
        $this->metadataTest($result->propertyMetadata['lastName'], 'contact.contacts.lastName', false, true);
        $this->metadataTest(
            $result->propertyMetadata['avatar'],
            'public.avatar',
            false,
            true,
            'thumbnails',
            '',
            '',
            false
        );
        $this->metadataTest(
            $result->propertyMetadata['fullName'],
            'public.name',
            true,
            false,
            'string',
            '100px',
            '50px',
            false,
            false,
            'test-class'
        );
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

        $this->metadataTest($result->propertyMetadata['id'], 'public.id', true, false, 'integer');
        $this->metadataTest($result->propertyMetadata['firstName'], 'contact.contacts.firstName', false, true);
        $this->metadataTest($result->propertyMetadata['lastName'], 'contact.contacts.lastName', false, true);
    }

    protected function metadataTest(
        PropertyMetadata $metadata,
        $translation,
        $disabled = false,
        $default = false,
        $type = 'string',
        $width = '',
        $minWith = '',
        $sortable = true,
        $editable = false,
        $cssClass = ''
    ) {
        self::assertEquals($translation, $metadata->getTranslation());
        self::assertEquals($disabled, $metadata->isDisabled());
        self::assertEquals($default, $metadata->isDefault());
        self::assertEquals($type, $metadata->getType());
        self::assertEquals($width, $metadata->getWidth());
        self::assertEquals($minWith, $metadata->getMinWidth());
        self::assertEquals($sortable, $metadata->isSortable());
        self::assertEquals($editable, $metadata->isEditable());
        self::assertEquals($cssClass, $metadata->getCssClass());
    }
}
