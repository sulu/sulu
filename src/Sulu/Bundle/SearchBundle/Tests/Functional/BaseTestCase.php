<?php

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase as SymfonyCmfBaseTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\DefaultStructureCache;

class BaseTestCase extends SymfonyCmfBaseTestCase
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

    public function getSearchManager()
    {
        $searchManager = $this->getContainer()->get('massive_search.search_manager');
        return $searchManager;
    }

    public function generateStructureIndex($count)
    {
        for ($i = 1; $i <= $count; $i++) {
            $structure = new DefaultStructureCache();
            $structure->setUuid($i);
            $structure->getProperty('title')->setValue('Structure Title ' . $i);
            $structure->getProperty('title')->setIndexed(true);

            $structure->getProperty('url')->setValue('/');
            $structure->getProperty('url')->setIndexed(false);

            $this->getSearchManager()->index($structure, 'content');
        }
    }

    public function indexStructure($title, $url)
    {
        $data = array(
            'title' => $title,
            'url' => $url
        );

        $session = $this->getContainer()->get('doctrine_phpcr')->getConnection();

        try {
            $node = $session->getNode('/cmf/sulu_io/routes/de/'.$url);
            $node->remove();
            $session->save();
        } catch (\PHPCR\PathNotFoundException $e) {
        }

        $mapper = $this->getContainer()->get('sulu.content.mapper');
        $mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, null);
    }
}

