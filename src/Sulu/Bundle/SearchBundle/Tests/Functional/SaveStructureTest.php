<?php

namespace Sulu\Bundle\SuluSearchBundle\Tests\Functional;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;
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
        $data = array(
            'title' => 'About Us',
            'url' => '/about-us'
        );

        $session = $this->getContainer()->get('doctrine_phpcr')->getConnection();

        try {
            $node = $session->getNode('/cmf/sulu_io/routes/de/about-us');
            $node->remove();
            $session->save();
        } catch (\PHPCR\PathNotFoundException $e) {
        }

        $mapper = $this->getContainer()->get('sulu.content.mapper');
        $mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, null);

        $searchManager = $this->getContainer()->get('massive_search.search_manager');
        $res = $searchManager->search('About*', 'content');
        $this->assertCount(1, $res);
    }
}
