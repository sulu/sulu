<?php

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;

class SaveStructureTest extends BaseTestCase
{
    public function getKernelConfiguration()
    {
        return array('environment' => 'dev');
    }

    public function setUp()
    {
        $fs = new Filesystem;
        $fs->remove(__DIR__ . '/../Resources/app/data');
    }

    public function testSaveStructure()
    {
        $this->indexStructure('About Us', '/about-us');

        $searchManager = $this->getSearchManager();
        $res = $searchManager->search('About*', 'de', 'content');
        $this->assertCount(1, $res);
        $hit = $res[0];
        $document = $hit->getDocument();

        $this->assertEquals('About Us', $document->getTitle());
        $this->assertEquals('/about-us', $document->getUrl());

        // ensure metadataload listener was called
        $metadataListener = $this->getContainer()->get('structure_metadata_load_listener');
        $this->assertInstanceOf('Sulu\Component\Content\StructureInterface', $metadataListener->structure);
        $this->assertInstanceOf('Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata', $metadataListener->indexMetadata);
    }
}
