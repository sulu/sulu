<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Metadata\Driver\FileLocatorInterface;
use Metadata\MetadataFactory;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Driver\XmlDriver as DoctrineXmlDriver;
use Sulu\Component\Rest\ListBuilder\Metadata\General\Driver\XmlDriver as GeneralXmlDriver;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\ChainProvider;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\MetadataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FieldDescriptorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFieldDescriptorForClassComplete()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/complete.xml');

        $parameterBag = $this->prophesize(ParameterBagInterface::class);
        $parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('SuluContactBundle:Contact');
        $parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'SuluContactBundle:Contact.avatar'
        );
        $parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'SuluContactBundle:Contact.contactAddresses'
        );
        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $chain = [
            new MetadataProvider(
                new MetadataFactory(new DoctrineXmlDriver($locator->reveal(), $parameterBag->reveal()))
            ),
            new MetadataProvider(new MetadataFactory(new GeneralXmlDriver($locator->reveal()))),
        ];
        $provider = new ChainProvider($chain);
        $factory = new FieldDescriptorFactory($provider);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        self::assertEquals(['id', 'firstName', 'lastName', 'avatar', 'fullName', 'city'], array_keys($fieldDescriptor));

        $this->fieldDescriptorTest($fieldDescriptor['id'], 'id', 'public.id', true, false, 'integer');
        $this->fieldDescriptorTest(
            $fieldDescriptor['firstName'],
            'firstName',
            'contact.contacts.firstName',
            false,
            true
        );
        $this->fieldDescriptorTest($fieldDescriptor['lastName'], 'lastName', 'contact.contacts.lastName', false, true);
        $this->fieldDescriptorTest(
            $fieldDescriptor['avatar'],
            'avatar',
            'public.avatar',
            false,
            true,
            'thumbnails',
            '',
            '',
            false
        );
        $this->fieldDescriptorTest(
            $fieldDescriptor['fullName'],
            'fullName',
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
        $this->fieldDescriptorTest($fieldDescriptor['city'], 'city', 'contact.address.city', false, true);

        self::assertInstanceOf(DoctrineConcatenationFieldDescriptor::class, $fieldDescriptor['fullName']);
        self::assertEquals(
            'CONCAT(SuluContactBundle:Contact.firstName, CONCAT(\' \', SuluContactBundle:Contact.lastName))',
            $fieldDescriptor['fullName']->getSelect()
        );
    }

    public function testGetFieldDescriptorForClassMinimal()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/minimal.xml');

        $parameterBag = $this->prophesize(ParameterBagInterface::class);
        $parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('SuluContactBundle:Contact');
        $parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'SuluContactBundle:Contact.avatar'
        );
        $parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'SuluContactBundle:Contact.contactAddresses'
        );
        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $chain = [
            new MetadataProvider(
                new MetadataFactory(new DoctrineXmlDriver($locator->reveal(), $parameterBag->reveal()))
            ),
            new MetadataProvider(new MetadataFactory(new GeneralXmlDriver($locator->reveal()))),
        ];
        $provider = new ChainProvider($chain);
        $factory = new FieldDescriptorFactory($provider);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        self::assertEquals(['id', 'firstName', 'lastName'], array_keys($fieldDescriptor));

        $this->fieldDescriptorTest($fieldDescriptor['id'], 'id', 'public.id', true, false, 'integer');
        $this->fieldDescriptorTest(
            $fieldDescriptor['firstName'],
            'firstName',
            'contact.contacts.firstName',
            false,
            true
        );
        $this->fieldDescriptorTest($fieldDescriptor['lastName'], 'lastName', 'contact.contacts.lastName', false, true);
    }

    public function testGetFieldDescriptorForClassEmpty()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/empty.xml');

        $parameterBag = $this->prophesize(ParameterBagInterface::class);
        $parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('SuluContactBundle:Contact');
        $parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'SuluContactBundle:Contact.avatar'
        );
        $parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'SuluContactBundle:Contact.contactAddresses'
        );
        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $chain = [
            new MetadataProvider(
                new MetadataFactory(new DoctrineXmlDriver($locator->reveal(), $parameterBag->reveal()))
            ),
            new MetadataProvider(new MetadataFactory(new GeneralXmlDriver($locator->reveal()))),
        ];
        $provider = new ChainProvider($chain);
        $factory = new FieldDescriptorFactory($provider);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        self::assertEmpty($fieldDescriptor);
    }

    protected function fieldDescriptorTest(
        FieldDescriptorInterface $fieldDescriptor,
        $name,
        $translation,
        $disabled = false,
        $default = false,
        $type = 'string',
        $width = '',
        $minWith = '',
        $sortable = true,
        $editable = false,
        $class = ''
    ) {
        self::assertEquals($name, $fieldDescriptor->getName());
        self::assertEquals($translation, $fieldDescriptor->getTranslation());
        self::assertEquals($disabled, $fieldDescriptor->getDisabled());
        self::assertEquals($default, $fieldDescriptor->getDefault());
        self::assertEquals($type, $fieldDescriptor->getType());
        self::assertEquals($width, $fieldDescriptor->getWidth());
        self::assertEquals($minWith, $fieldDescriptor->getMinWidth());
        self::assertEquals($sortable, $fieldDescriptor->getSortable());
        self::assertEquals($editable, $fieldDescriptor->getEditable());
        self::assertEquals($class, $fieldDescriptor->getClass());
    }
}
