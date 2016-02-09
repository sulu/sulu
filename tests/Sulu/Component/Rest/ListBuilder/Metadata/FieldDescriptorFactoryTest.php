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
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Driver\XmlDriver as DoctrineXmlDriver;
use Sulu\Component\Rest\ListBuilder\Metadata\General\Driver\XmlDriver as GeneralXmlDriver;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\ChainProvider;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\MetadataProvider;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FieldDescriptorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigCache
     */
    private $configCache;

    /**
     * @var FileLocatorInterface
     */
    private $locator;

    /**
     * @var ProviderInterface[]
     */
    private $chain;

    public function setup()
    {
        parent::setUp();

        $this->locator = $this->prophesize(FileLocatorInterface::class);

        $parameterBag = $this->prophesize(ParameterBagInterface::class);
        $parameterBag->resolveValue('%sulu.model.contact.class%')->willReturn('SuluContactBundle:Contact');
        $parameterBag->resolveValue('%sulu.model.contact.class%.avatar')->willReturn(
            'SuluContactBundle:Contact.avatar'
        );
        $parameterBag->resolveValue('%sulu.model.contact.class%.contactAddresses')->willReturn(
            'SuluContactBundle:Contact.contactAddresses'
        );
        $parameterBag->resolveValue(Argument::any())->willReturnArgument(0);

        $this->configCache = $this->prophesize(ConfigCache::class);
        $this->configCache->isFresh()->willReturn(false);
        $this->configCache->write(Argument::any(), null)->willReturn(null);

        $this->chain = [
            new MetadataProvider(
                new MetadataFactory(new DoctrineXmlDriver($this->locator->reveal(), $parameterBag->reveal()))
            ),
            new MetadataProvider(new MetadataFactory(new GeneralXmlDriver($this->locator->reveal()))),
        ];
    }

    public function testGetFieldDescriptorForClassComplete()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/complete.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCache->reveal());
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEquals(
            ['id', 'firstName', 'lastName', 'avatar', 'fullName', 'city'],
            array_keys($fieldDescriptor)
        );

        $expected = [
            'id' => ['name' => 'id', 'translation' => 'public.id', 'disabled' => true, 'type' => 'integer'],
            'firstName' => ['name' => 'firstName', 'translation' => 'contact.contacts.firstName', 'default' => true],
            'lastName' => ['name' => 'lastName', 'translation' => 'contact.contacts.lastName', 'default' => true],
            'avatar' => [
                'name' => 'avatar',
                'translation' => 'public.avatar',
                'default' => true,
                'type' => 'thumbnails',
                'sortable' => false
            ],
            'fullName' => [
                'instance' => DoctrineConcatenationFieldDescriptor::class,
                'name' => 'fullName',
                'translation' => 'public.name',
                'disabled' => true,
                'sortable' => false,
                'class' => 'test-class',
                'minWidth' => '50px',
                'width' => '100px',
                'select' => 'CONCAT(SuluContactBundle:Contact.firstName, CONCAT(\' \', SuluContactBundle:Contact.lastName))'
            ],
            'city' => ['name' => 'city', 'translation' => 'contact.address.city', 'default' => true],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassMinimal()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/minimal.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCache->reveal());
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEquals(['id', 'firstName', 'lastName'], array_keys($fieldDescriptor));

        $expected = [
            'id' => ['name' => 'id', 'translation' => 'public.id', 'disabled' => true, 'type' => 'integer'],
            'firstName' => ['name' => 'firstName', 'translation' => 'contact.contacts.firstName', 'default' => true],
            'lastName' => ['name' => 'lastName', 'translation' => 'contact.contacts.lastName', 'default' => true],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassEmpty()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/empty.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCache->reveal());
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEmpty($fieldDescriptor);
    }

    protected function assertFieldDescriptors(array $expected, array $fieldDescriptors)
    {
        foreach ($expected as $name => $expectedData) {
            $this->assertFieldDescriptor($expectedData, $fieldDescriptors[$name]);
        }
    }

    protected function assertFieldDescriptor(array $expected, FieldDescriptorInterface $fieldDescriptor)
    {
        $expected = array_merge(
            [
                'instance' => DoctrineFieldDescriptor::class,
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

        $this->assertInstanceOf($expected['instance'], $fieldDescriptor);
        $this->assertEquals($expected['name'], $fieldDescriptor->getName());
        $this->assertEquals($expected['translation'], $fieldDescriptor->getTranslation());
        $this->assertEquals($expected['disabled'], $fieldDescriptor->getDisabled());
        $this->assertEquals($expected['default'], $fieldDescriptor->getDefault());
        $this->assertEquals($expected['type'], $fieldDescriptor->getType());
        $this->assertEquals($expected['width'], $fieldDescriptor->getWidth());
        $this->assertEquals($expected['minWidth'], $fieldDescriptor->getMinWidth());
        $this->assertEquals($expected['sortable'], $fieldDescriptor->getSortable());
        $this->assertEquals($expected['editable'], $fieldDescriptor->getEditable());
        $this->assertEquals($expected['class'], $fieldDescriptor->getClass());
    }
}
