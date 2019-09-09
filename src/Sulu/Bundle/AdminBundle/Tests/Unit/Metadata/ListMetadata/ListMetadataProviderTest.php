<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\ListMetadata;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\XmlListMetadataLoader;

class ListMetadataProviderTest extends TestCase
{
    /**
     * @var ListMetadataProvider
     */
    private $listMetadataProvider;

    /**
     * @var XmlListMetadataLoader
     */
    private $xmlListMetadataLoader1;

    /**
     * @var XmlListMetadataLoader
     */
    private $xmlListMetadataLoader2;

    public function setUp(): void
    {
        $this->xmlListMetadataLoader1 = $this->prophesize(ListMetadataLoaderInterface::class);
        $this->xmlListMetadataLoader2 = $this->prophesize(ListMetadataLoaderInterface::class);

        $loaders = [$this->xmlListMetadataLoader1->reveal(), $this->xmlListMetadataLoader2->reveal()];
        $this->listMetadataProvider = new ListMetadataProvider($loaders);
    }

    public function testGetMetadataFromLoader1()
    {
        $listMetadata = new ListMetadata();
        $listMetadata->addField(new FieldMetadata('field1'));
        $listMetadata->addField(new FieldMetadata('field2'));
        $listMetadata->addField(new FieldMetadata('field3'));

        $this->xmlListMetadataLoader1->getMetadata('list1', 'en', [])->willReturn($listMetadata);
        $this->xmlListMetadataLoader2->getMetadata('list1', 'en', [])->willReturn(null);

        $metadata = $this->listMetadataProvider->getMetadata('list1', 'en');
        $this->assertEquals($listMetadata, $metadata);
    }

    public function testGetMetadataFromLoader2()
    {
        $listMetadata = new ListMetadata();
        $listMetadata->addField(new FieldMetadata('field1'));
        $listMetadata->addField(new FieldMetadata('field2'));
        $listMetadata->addField(new FieldMetadata('field3'));

        $this->xmlListMetadataLoader1->getMetadata('list1', 'en', [])->willReturn(null);
        $this->xmlListMetadataLoader2->getMetadata('list1', 'en', [])->willReturn($listMetadata);

        $metadata = $this->listMetadataProvider->getMetadata('list1', 'en');
        $this->assertEquals($listMetadata, $metadata);
    }

    public function testGetMetadataNotExisting()
    {
        $this->xmlListMetadataLoader1->getMetadata('list1', 'en', [])->willReturn(null);
        $this->xmlListMetadataLoader2->getMetadata('list1', 'en', [])->willReturn(null);

        $this->expectException(MetadataNotFoundException::class);
        $this->listMetadataProvider->getMetadata('list1', 'en');
    }
}
