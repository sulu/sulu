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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataVisitorInterface;

class ListMetadataProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ListMetadataProvider
     */
    private $listMetadataProvider;

    /**
     * @var ObjectProphecy<ListMetadataLoaderInterface>
     */
    private $xmlListMetadataLoader1;

    /**
     * @var ObjectProphecy<ListMetadataLoaderInterface>
     */
    private $xmlListMetadataLoader2;

    /**
     * @var ObjectProphecy<ListMetadataVisitorInterface>
     */
    private $listMetadataVisitor1;

    /**
     * @var ObjectProphecy<ListMetadataVisitorInterface>
     */
    private $listMetadataVisitor2;

    public function setUp(): void
    {
        $this->xmlListMetadataLoader1 = $this->prophesize(ListMetadataLoaderInterface::class);
        $this->xmlListMetadataLoader2 = $this->prophesize(ListMetadataLoaderInterface::class);
        $this->listMetadataVisitor1 = $this->prophesize(ListMetadataVisitorInterface::class);
        $this->listMetadataVisitor2 = $this->prophesize(ListMetadataVisitorInterface::class);

        $loaders = [$this->xmlListMetadataLoader1->reveal(), $this->xmlListMetadataLoader2->reveal()];
        $visitors = [$this->listMetadataVisitor1->reveal(), $this->listMetadataVisitor2->reveal()];
        $this->listMetadataProvider = new ListMetadataProvider($loaders, $visitors);
    }

    public function testGetMetadataFromLoader1(): void
    {
        $listMetadata = new ListMetadata();
        $listMetadata->addField(new FieldMetadata('field1'));
        $listMetadata->addField(new FieldMetadata('field2'));
        $listMetadata->addField(new FieldMetadata('field3'));

        $this->xmlListMetadataLoader1->getMetadata('list1', 'en', [])->willReturn($listMetadata)->shouldBeCalled();
        $this->xmlListMetadataLoader2->getMetadata('list1', 'en', [])->shouldNotBeCalled();

        $this->listMetadataVisitor1->visitListMetadata($listMetadata, 'list1', 'en', Argument::any())
            ->shouldBeCalled();
        $this->listMetadataVisitor2->visitListMetadata($listMetadata, 'list1', 'en', Argument::any())
            ->shouldBeCalled();

        $metadata = $this->listMetadataProvider->getMetadata('list1', 'en');
        $this->assertEquals($listMetadata, $metadata);
    }

    public function testGetMetadataFromLoader2(): void
    {
        $listMetadata = new ListMetadata();
        $listMetadata->addField(new FieldMetadata('field1'));
        $listMetadata->addField(new FieldMetadata('field2'));
        $listMetadata->addField(new FieldMetadata('field3'));

        $this->xmlListMetadataLoader1->getMetadata('list1', 'en', [])->willReturn(null)->shouldBeCalled();
        $this->xmlListMetadataLoader2->getMetadata('list1', 'en', [])->willReturn($listMetadata)->shouldBeCalled();

        $this->listMetadataVisitor1->visitListMetadata($listMetadata, 'list1', 'en', Argument::any())
            ->shouldBeCalled();
        $this->listMetadataVisitor2->visitListMetadata($listMetadata, 'list1', 'en', Argument::any())
            ->shouldBeCalled();

        $metadata = $this->listMetadataProvider->getMetadata('list1', 'en');
        $this->assertEquals($listMetadata, $metadata);
    }

    public function testGetMetadataNotExisting(): void
    {
        $this->xmlListMetadataLoader1->getMetadata('list1', 'en', [])->willReturn(null)->shouldBeCalled();
        $this->xmlListMetadataLoader2->getMetadata('list1', 'en', [])->willReturn(null)->shouldBeCalled();

        $this->listMetadataVisitor1->visitListMetadata(Argument::cetera())
            ->shouldNotBeCalled();
        $this->listMetadataVisitor2->visitListMetadata(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(MetadataNotFoundException::class);
        $this->listMetadataProvider->getMetadata('list1', 'en');
    }
}
