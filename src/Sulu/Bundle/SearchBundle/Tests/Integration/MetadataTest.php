<?php

namespace Integration;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;

class MetadataTest extends BaseTestCase
{
    public function setUp()
    {
        $this->metadataFactory = $this->getContainer()->get('sulu_search.metadata.factory');
    }

    public function testMetadataFactory()
    {
        $metadata = $this->metadataFactory->getMetadataForClass('Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product');
        $metadata = $metadata->getOutsideClassMetadata();

        $this->assertEquals(array(
            'title' => array(
                'type' => 'string',
            ),
            'body' => array(
                'type' => 'string',
            ),
        ), $metadata->getFieldMapping());

        $this->assertEquals('product', $metadata->getIndexName());
        $this->assertEquals('id', $metadata->getIdField());
    }
}
