<?php

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\SecondStructureCache;
use Sulu\Component\Content\StructureInterface;

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

    /**
     * Check that the automatic indexing works
     */
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
        $this->assertEquals(null, $document->getDescription());

        // ensure metadataload listener was called
        $metadataListener = $this->getContainer()->get('structure_metadata_load_listener');
        $this->assertInstanceOf('Sulu\Component\Content\StructureInterface', $metadataListener->structure);
        $this->assertInstanceOf('Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata', $metadataListener->indexMetadata);
    }

    /**
     * Test that the tagged "description" field is indexed.
     */
    public function testSaveSecondStructure()
    {
        $searchManager = $this->getSearchManager();

        $structure = new SecondStructureCache();
        $structure->setUuid(123);
        $structure->getProperty('title')->setValue('This is a title');
        $structure->getProperty('article')->setValue('out with colleagues. Following a highly publicised appeal for information on her');
        $structure->getProperty('url')->setValue('/');
        $structure->getProperty('images')->setValue(array('asd'));

        $structure->setNodeState(StructureInterface::STATE_PUBLISHED);
        $searchManager->index($structure, 'de', 'content');

        $res = $searchManager->search('colleagues', 'de', 'content');
        $this->assertCount(1, $res);
    }
}
