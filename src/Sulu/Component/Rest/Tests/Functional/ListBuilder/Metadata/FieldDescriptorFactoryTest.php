<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Metadata;

use Metadata\Driver\FileLocatorInterface;
use Metadata\MetadataFactory;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineConcatenationFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCountFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineIdentityFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Driver\XmlDriver as DoctrineXmlDriver;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactory;
use Sulu\Component\Rest\ListBuilder\Metadata\General\Driver\XmlDriver as GeneralXmlDriver;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\ChainProvider;
use Sulu\Component\Rest\ListBuilder\Metadata\Provider\MetadataProvider;
use Sulu\Component\Rest\ListBuilder\Metadata\ProviderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class FieldDescriptorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $configCachePath;

    /**
     * @var bool
     */
    private $debug = false;

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

        $filesystem = new Filesystem();
        $this->configCachePath = __DIR__ . '/cache';
        if ($filesystem->exists($this->configCachePath)) {
            $filesystem->remove($this->configCachePath);
        }
        $filesystem->mkdir($this->configCachePath);

        $this->chain = [
            new MetadataProvider(
                new MetadataFactory(new DoctrineXmlDriver($this->locator->reveal(), $parameterBag->reveal()))
            ),
            new MetadataProvider(
                new MetadataFactory(new GeneralXmlDriver($this->locator->reveal(), $parameterBag->reveal()))
            ),
        ];
    }

    public function testGetFieldDescriptorForClassComplete()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/complete.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
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
                'sortable' => false,
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
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEquals(['id', 'firstName', 'lastName'], array_keys($fieldDescriptor));

        $expected = [
            'id' => ['name' => 'id', 'translation' => 'public.id', 'disabled' => true, 'type' => 'integer'],
            'firstName' => ['name' => 'firstName', 'translation' => 'contact.contacts.firstName', 'default' => true],
            'lastName' => ['name' => 'lastName', 'translation' => 'contact.contacts.lastName', 'default' => true],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassGroupConcat()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/group-concat.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEquals(['tags'], array_keys($fieldDescriptor));

        $expected = [
            'tags' => [
                'name' => 'tags',
                'translation' => 'Tags',
                'instance' => DoctrineGroupConcatFieldDescriptor::class,
                'disabled' => true,
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassCase()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/case.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEquals(['tag'], array_keys($fieldDescriptor));

        $expected = [
            'tag' => [
                'name' => 'tag',
                'translation' => 'Tag',
                'instance' => DoctrineCaseFieldDescriptor::class,
                'disabled' => true,

            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);

        $this->assertEquals(
            '(CASE WHEN SuluTagBundle:Tag.name IS NOT NULL THEN SuluTagBundle:Tag.name ELSE SuluTagBundle:Tag.name END)',
            $fieldDescriptor['tag']->getSelect()
        );
    }

    public function testGetFieldDescriptorForClassIdentity()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/identity.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEquals(['tags'], array_keys($fieldDescriptor));

        $expected = [
            'tags' => [
                'name' => 'tags',
                'translation' => 'Tags',
                'instance' => DoctrineIdentityFieldDescriptor::class,
                'disabled' => true,
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassOptions()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/options.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class, ['locale' => 'de']);

        $this->assertEquals(['city'], array_keys($fieldDescriptor));

        $expected = [
            'city' => [
                'name' => 'city',
                'translation' => 'City',
                'disabled' => true,
                'joins' => [
                    'SuluContactBundle:ContactAddress' => [
                        'entity-name' => 'SuluContactBundle:ContactAddress',
                        'field-name' => 'SuluContactBundle:Contact.contactAddresses',
                        'method' => 'LEFT',
                        'condition' => 'SuluContactBundle:ContactAddress.locale = \'de\'',
                    ],
                ],
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassCount()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/count.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEquals(['tags'], array_keys($fieldDescriptor));

        $expected = [
            'tags' => [
                'name' => 'tags',
                'translation' => 'Tags',
                'instance' => DoctrineCountFieldDescriptor::class,
                'disabled' => true,
            ],
        ];

        $this->assertFieldDescriptors($expected, $fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassEmpty()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/empty.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertEmpty($fieldDescriptor);
    }

    public function testGetFieldDescriptorForClassMixed()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/mixed.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class);

        $this->assertCount(2, $fieldDescriptor);
        $this->assertFieldDescriptor(
            ['name' => 'id', 'translation' => 'Id', 'disabled' => true],
            $fieldDescriptor['id']
        );
        $this->assertFieldDescriptor(
            ['name' => 'name', 'translation' => 'Name', 'disabled' => true, 'instance' => FieldDescriptor::class],
            $fieldDescriptor['name']
        );
    }

    public function testGetFieldDescriptorForClassMixedByType()
    {
        $this->locator->findFileForClass(new \ReflectionClass(new \stdClass()), 'xml')
            ->willReturn(__DIR__ . '/Resources/mixed.xml');

        $provider = new ChainProvider($this->chain);
        $factory = new FieldDescriptorFactory($provider, $this->configCachePath, $this->debug);
        $fieldDescriptor = $factory->getFieldDescriptorForClass(\stdClass::class, [], DoctrineFieldDescriptor::class);

        $this->assertCount(1, $fieldDescriptor);
        $this->assertFieldDescriptor(
            ['name' => 'id', 'translation' => 'Id', 'disabled' => true],
            $fieldDescriptor['id']
        );
    }

    private function assertFieldDescriptors(array $expected, array $fieldDescriptors)
    {
        foreach ($expected as $name => $expectedData) {
            $this->assertFieldDescriptor($expectedData, $fieldDescriptors[$name]);
        }
    }

    private function assertFieldDescriptor(array $expected, FieldDescriptorInterface $fieldDescriptor)
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

        if (array_key_exists('joins', $expected)) {
            foreach ($expected['joins'] as $name => $joinExpected) {
                $this->assertJoin($joinExpected, $fieldDescriptor->getJoins()[$name]);
            }
        }
    }

    private function assertJoin(array $expected, DoctrineJoinDescriptor $join)
    {
        $expected = array_merge(
            [
                'entity-name' => null,
                'field-name' => null,
                'method' => null,
                'condition' => null,
            ],
            $expected
        );

        $this->assertEquals($expected['entity-name'], $join->getEntityName());
        $this->assertEquals($expected['field-name'], $join->getJoin());
        $this->assertEquals($expected['method'], $join->getJoinMethod());
        $this->assertEquals($expected['condition'], $join->getJoinCondition());
    }
}
