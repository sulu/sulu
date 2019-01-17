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

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\DatagridMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\JoinMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\CountTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\GroupConcatTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\IdentityTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\DatagridXmlLoader;
use Sulu\Component\Rest\ListBuilder\Metadata\AbstractAbstractPropertyMetadata;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DatagridXmlLoaderTest extends TestCase
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var DatagridXmlLoader
     */
    private $datagridXmlLoader;

    protected function setUp()
    {
        parent::setUp();

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

        $this->datagridXmlLoader = new XmlDriver($this->parameterBag->reveal());
    }

    public function testLoadMetadataFromFileComplete()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/complete.xml');
        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('complete', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(6, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'id',
                'entityName' => 'SuluContactBundle:Contact',
                'translation' => 'public.id',
                'type' => 'integer',
            ],
            $propertiesMetadata[0]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'firstName',
                'entityName' => 'SuluContactBundle:Contact',
                'translation' => 'contact.contacts.firstName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
            ],
            $propertiesMetadata[1]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'lastName',
                'entityName' => 'SuluContactBundle:Contact',
                'translation' => 'contact.contacts.lastName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
            ],
            $propertiesMetadata[2]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'avatar',
                'entityName' => 'SuluMediaBundle:Media',
                'translation' => 'public.avatar',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'type' => 'thumbnails',
                'sortable' => false,
                'joins' => [
                    [
                        'entityName' => 'SuluMediaBundle:Media',
                        'entityField' => 'SuluContactBundle:Contact.avatar',
                    ],
                ],
            ],
            $propertiesMetadata[3]
        );
        $this->assertConcatenationMetadata(
            [
                'name' => 'fullName',
                'translation' => 'public.name',
                'width' => '100px',
                'minWidth' => '50px',
                'sortable' => false,
                'class' => 'test-class',
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
            $propertiesMetadata[4]
        );
    }

    public function testLoadMetadataFromFileEmpty()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/empty.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('empty', $result->getKey());
        $this->assertCount(0, $result->getPropertiesMetadata());
    }

    public function testLoadMetadataFromFileMinimal()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/minimal.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('minimal', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(3, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'id',
                'translation' => 'public.id',
                'type' => 'integer',
                'entityName' => 'SuluContactBundle:Contact',
            ],
            $propertiesMetadata[0]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'firstName',
                'translation' => 'contact.contacts.firstName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_YES,
                'entityName' => 'SuluContactBundle:Contact',
            ],
            $propertiesMetadata[1]
        );
        $this->assertSingleMetadata(
            [
                'name' => 'lastName',
                'translation' => 'contact.contacts.lastName',
                'visibility' => FieldDescriptorInterface::VISIBILITY_ALWAYS,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_NO,
                'entityName' => 'SuluContactBundle:Contact',
            ],
            $propertiesMetadata[2]
        );
    }

    public function testLoadMetadataFromFileInputType()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/filter-type.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('filter-type', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test-input',
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileParameters()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/filter-type-parameters.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('filter-type-parameters', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => 'test-input',
                'filter-type-parameters' => [
                    'test1' => 'test-value',
                    'test2' => 'test',
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileNoInputType()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/filter-type-no-input.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('filter-type-no-input', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertSingleMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'filter-type' => null,
                'filter-type-parameters' => [
                    'test1' => 'test-value',
                    'test2' => 'test',
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileGroupConcat()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/group-concat.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('group-concat', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertGroupConcatMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'entityName' => 'SuluTagBundle:Tag',
                'joins' => [
                    [
                        'entityName' => 'SuluTagBundle:Tag',
                        'entityField' => 'SuluContactBundle:Contact.tags',
                    ],
                ],
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileIdentity()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/identity.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('identity', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertIdentityMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'entityName' => 'SuluContactBundle:Contact',
            ],
            $propertiesMetadata[0]
        );
    }

    public function testLoadMetadataFromFileCount()
    {
        $result = $this->datagridXmlLoader->load(__DIR__ . '/Resources/count.xml');

        $this->assertInstanceOf(DatagridMetadata::class, $result);
        $this->assertEquals('count', $result->getKey());

        $propertiesMetadata = $result->getPropertiesMetadata();
        $this->assertCount(1, $propertiesMetadata);

        $this->assertCountMetadata(
            [
                'name' => 'tags',
                'translation' => 'Tags',
                'entityName' => 'SuluContactBundle:Contact',
            ],
            $propertiesMetadata[0]
        );
    }

    private function assertSingleMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(SingleTypeMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertGroupConcatMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(GroupConcatTypeMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertIdentityMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(IdentityTypeMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertCountMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $this->assertInstanceOf(CountTypeMetadata::class, $metadata);
        $this->assertPropertyMetadata($expected, $metadata);
    }

    private function assertPropertyMetadata(array $expected, AbstractPropertyMetadata $metadata)
    {
        $expected = array_merge(
            [
                'instance' => AbstractPropertyMetadata::class,
                'name' => null,
                'translation' => null,
                'visibility' => FieldDescriptorInterface::VISIBILITY_NO,
                'searchability' => FieldDescriptorInterface::SEARCHABILITY_NEVER,
                'type' => 'string',
                'width' => '',
                'minWidth' => '',
                'sortable' => true,
                'editable' => false,
                'class' => '',
                'filter-type' => null,
                'filter-type-parameters' => [],
                'entityName' => null,
                'joins' => [],
            ],
            $expected
        );

        $this->assertEquals($expected['name'], $metadata->getName());
        $this->assertEquals($expected['translation'], $metadata->getTranslation());
        $this->assertEquals($expected['filter-type'], $metadata->getFilterType());
        $this->assertEquals($expected['filter-type-parameters'], $metadata->getFilterTypeParameters());
        $this->assertEquals($expected['visibility'], $metadata->getVisibility());
        $this->assertEquals($expected['searchability'], $metadata->getSearchability());

        $this->assertEquals($expected['type'], $metadata->getType());
        $this->assertEquals($expected['width'], $metadata->getWidth());
        $this->assertEquals($expected['minWidth'], $metadata->getMinWidth());
        $this->assertEquals($expected['sortable'], $metadata->isSortable());
        $this->assertEquals($expected['editable'], $metadata->isEditable());
        $this->assertEquals($expected['class'], $metadata->getCssClass());

        if ($metadata->getField()) {
            $this->assertFieldMetadata($expected, $metadata->getField());
        }
    }

    private function assertFieldMetadata(array $expected, FieldMetadata $fieldMetadata)
    {
        $expected = array_merge(
            [
                'joins' => [],
            ],
            $expected
        );

        $this->assertEquals($expected['entityName'], $fieldMetadata->getEntityName());
        $this->assertCount(count($expected['joins']), $fieldMetadata->getJoins());

        $i = 0;
        foreach ($expected['joins'] as $joinExpected) {
            $this->assertJoin($joinExpected, $fieldMetadata->getJoins()[$i]);
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

    private function assertConcatenationMetadata($expected, AbstractPropertyMetadata $metadata)
    {
        $expected = array_merge(
            [
                'glue' => null,
                'fields' => [],
            ],
            $expected
        );

        $this->assertInstanceOf(ConcatenationTypeMetadata::class, $metadata);

        $this->assertEquals($expected['glue'], $metadata->getGlue());
        $this->assertCount(count($expected['fields']), $metadata->getFields());

        $i = 0;
        foreach ($expected['fields'] as $fieldExpected) {
            $this->assertFieldMetadata($fieldExpected, $metadata->getFields()[$i]);
            ++$i;
        }
    }
}
