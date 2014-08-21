<?php

namespace Unit\Search;

use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Argument;
use Sulu\Bundle\SearchBundle\Search\SearchManager;

class SearchManagerTest extends ProphecyTestCase
{ 
    public function setUp()
    {
        $this->adapter = $this->prophesize('Sulu\Bundle\SearchBundle\Search\AdapterInterface');
        $this->metadataFactory = $this->prophesize('Metadata\MetadataFactory');
        $this->metadata = $this->prophesize('Sulu\Bundle\SearchBundle\Search\Metadata\IndexMetadata');
        $this->classHierachyMetadata = $this->prophesize('Metadata\ClassHierarchyMetadata');
        $this->classHierachyMetadata->getOutsideClassMetadata()->willReturn($this->metadata);
        $this->searchManager = new SearchManager($this->adapter->reveal(), $this->metadataFactory->reveal());

        $this->product = new \Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIndexNonObject()
    {
        $this->searchManager->index('asd');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage There is no search mappin
     */
    public function testIndexNoMetadata()
    {
        $this->metadataFactory
            ->getMetadataForClass('Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product')
            ->willReturn(null);

        $this->searchManager->index($this->product);
    }

    public function testIndex()
    {
        $this->metadataFactory
            ->getMetadataForClass('Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product')
            ->willReturn($this->classHierachyMetadata);
        $this->metadata->getIdField()->willReturn('id');
        $this->metadata->getFieldMapping()->willReturn(array(
            'title' => array(
                'type' => 'string',
            ),
            'body' => array(
                'type' => 'string',
            )
        ));
        $this->metadata->getIndexName()->willReturn('product');

        $this->searchManager->index($this->product);
        $this->adapter->index(Argument::type('Sulu\Bundle\SearchBundle\Search\Document'));
    }
}
