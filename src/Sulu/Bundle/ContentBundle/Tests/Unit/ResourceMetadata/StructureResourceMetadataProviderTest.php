<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\ResourceMetadata;

use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid\Datagrid;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Endpoint\EndpointInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Form\Form;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataMapper;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\SchemaInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Type\TypesInterface;
use Sulu\Bundle\AdminBundle\ResourceMetadata\TypedResourceMetadata;
use Sulu\Bundle\ContentBundle\ResourceMetadata\StructureResourceMetadataProvider;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\StructureMetadata;

class StructureResourceMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureResourceMetadataProvider
     */
    private $structureResourceMetadataProvider;

    /**
     * @var StructureMetadataFactory
     */
    private $structureMetadataFactory;

    /**
     * @var ResourceMetadataMapper
     */
    private $resourceMetadataMapper;

    public function setUp()
    {
        $resourcesConfig = [
            'pages' => [
                'datagrid' => 'PageDocumentClass',
                'types' => ['page', 'home'],
                'endpoint' => 'get_pages',
            ],
            'snippets' => [
                'datagrid' => 'SnippetDocumentClass',
                'types' => ['snippet'],
                'endpoint' => 'get_snippets',
            ],
        ];

        $formMetadata = $this->prophesize(FormMetadata::class);
        $formMetadata->getProperties()->willReturn(['property_array']);
        $formMetadata->getChildren()->willReturn(['children_array']);

        $formMetadata2 = $this->prophesize(FormMetadata::class);
        $formMetadata2->getProperties()->willReturn(['property_array2']);
        $formMetadata2->getChildren()->willReturn(['children_array2']);

        $formMetadataAccount = $this->prophesize(FormMetadata::class);
        $formMetadataAccount->getProperties()->willReturn(['property_array_account']);
        $formMetadataAccount->getChildren()->willReturn(['children_array_account']);

        $this->resourceMetadataMapper = $this->prophesize(ResourceMetadataMapper::class);

        $this->resourceMetadataMapper->mapSchema(['property_array', 'property_array2'])->willReturn(new Schema());
        $this->resourceMetadataMapper->mapSchema(['property_array_account'])->willReturn(new Schema());

        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactory::class);

        $this->structureResourceMetadataProvider = new StructureResourceMetadataProvider(
            $this->structureMetadataFactory->reveal(),
            $this->resourceMetadataMapper->reveal(),
            $resourcesConfig
        );
    }

    public function testGeResourceMetadata()
    {
        $this->resourceMetadataMapper->mapDatagrid('PageDocumentClass', 'de')->willReturn(new Datagrid());

        $this->resourceMetadataMapper->mapForm(['children_test1'], 'de')
            ->willReturn(new Form());
        $this->resourceMetadataMapper->mapForm(['children_test2'], 'de')
            ->willReturn(new Form());

        $this->resourceMetadataMapper->mapSchema(['properties_test1'])
            ->willReturn(new Schema());
        $this->resourceMetadataMapper->mapSchema(['properties_test2'])
            ->willReturn(new Schema());

        $structure1 = $this->prophesize(StructureMetadata::class);
        $structure1->getName()->willReturn('test1');
        $structure1->getTitle('de')->willReturn('de');
        $structure1->isInternal()->willReturn(false);
        $structure1->getProperties()->willReturn(['properties_test1']);
        $structure1->getChildren()->willReturn(['children_test1']);

        $structure2 = $this->prophesize(StructureMetadata::class);
        $structure2->getName()->willReturn('test2');
        $structure2->getTitle('de')->willReturn('de');
        $structure2->isInternal()->willReturn(false);
        $structure2->getProperties()->willReturn(['properties_test2']);
        $structure2->getChildren()->willReturn(['children_test2']);

        $structures = [
            $structure1,
            $structure2,
        ];
        $this->structureMetadataFactory->getStructures('page')->willReturn($structures);
        $this->structureMetadataFactory->getStructures('home')->willReturn($structures);

        $structureResourceMetadata = $this->structureResourceMetadataProvider->getResourceMetadata(
            'pages',
            'de'
        );

        $this->assertNotNull($structureResourceMetadata);
        $this->assertInstanceOf(
            ResourceMetadataInterface::class,
            $structureResourceMetadata
        );
        $this->assertInstanceOf(
            EndpointInterface::class,
            $structureResourceMetadata
        );
        $this->assertInstanceOf(
            TypesInterface::class,
            $structureResourceMetadata
        );
        $this->assertInstanceOf(
            TypesInterface::class,
            $structureResourceMetadata
        );
        $this->assertEquals(
            'pages',
            $structureResourceMetadata->getKey()
        );
    }

    public function testGetAll()
    {
        $this->resourceMetadataMapper->mapDatagrid('PageDocumentClass', 'de')->willReturn(new Datagrid());
        $this->resourceMetadataMapper->mapDatagrid('SnippetDocumentClass', 'de')->willReturn(new Datagrid());

        $this->resourceMetadataMapper->mapForm(['children_test1'], 'de')
            ->willReturn(new Form());
        $this->resourceMetadataMapper->mapForm(['children_test2'], 'de')
            ->willReturn(new Form());
        $this->resourceMetadataMapper->mapForm(['children_snippets'], 'de')
            ->willReturn(new Form());

        $this->resourceMetadataMapper->mapSchema(['properties_test1'])
            ->willReturn(new Schema());
        $this->resourceMetadataMapper->mapSchema(['properties_test2'])
            ->willReturn(new Schema());
        $this->resourceMetadataMapper->mapSchema(['properties_snippets'])
            ->willReturn(new Schema());

        $structure1 = $this->prophesize(StructureMetadata::class);
        $structure1->getName()->willReturn('test1');
        $structure1->getTitle('de')->willReturn('de');
        $structure1->isInternal()->willReturn(false);
        $structure1->getProperties()->willReturn(['properties_test1']);
        $structure1->getChildren()->willReturn(['children_test1']);

        $structure2 = $this->prophesize(StructureMetadata::class);
        $structure2->getName()->willReturn('test2');
        $structure2->getTitle('de')->willReturn('de');
        $structure2->isInternal()->willReturn(false);
        $structure2->getProperties()->willReturn(['properties_test2']);
        $structure2->getChildren()->willReturn(['children_test2']);

        $snippetStructure = $this->prophesize(StructureMetadata::class);
        $snippetStructure->getName()->willReturn('test2');
        $snippetStructure->getTitle('de')->willReturn('de');
        $snippetStructure->isInternal()->willReturn(false);
        $snippetStructure->getProperties()->willReturn(['properties_snippets']);
        $snippetStructure->getChildren()->willReturn(['children_snippets']);

        $structures = [
            $structure1,
            $structure2,
        ];
        $this->structureMetadataFactory->getStructures('page')->willReturn($structures);
        $this->structureMetadataFactory->getStructures('home')->willReturn($structures);
        $this->structureMetadataFactory->getStructures('snippet')->willReturn([$snippetStructure]);

        $structureResourceMetadata = $this->structureResourceMetadataProvider->getAllResourceMetadata('de');
        $this->assertCount(2, $structureResourceMetadata);
    }

    public function testGetUnknownResource()
    {
        $this->structureMetadataFactory->getStructures()->shouldNotBeCalled();
        $this->assertNull($this->structureResourceMetadataProvider->getResourceMetadata('unknown_key', 'de'));
    }
}
