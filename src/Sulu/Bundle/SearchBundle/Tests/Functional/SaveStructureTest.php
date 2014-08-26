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
        $this->indexStructure('About Us', 'about-us');

        $searchManager = $this->getContainer()->get('massive_search.search_manager');
        $res = $searchManager->search('About*', 'content');
        $this->assertCount(1, $res);
        $hit = $res[0];
        $document = $hit->getDocument();

        $this->assertEquals('About Us', $document->getTitle());
        $this->assertEquals('about-us', $document->getUrl());
    }
}
