<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Structure;
use Symfony\Component\Filesystem\Filesystem;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\DefaultStructureCache;
use Sulu\Component\Content\StructureInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class BaseTestCase extends SuluTestCase
{
    protected $session;

    public function setUp()
    {
        $this->initPhpcr();
        $fs = new Filesystem;
        $fs->remove(__DIR__ . '/../app/data');

        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
    }

    public function getSearchManager()
    {
        $searchManager = $this->getContainer()->get('massive_search.search_manager');

        return $searchManager;
    }

    public function generateStructureIndex($count, $webspaceName = 'sulu_io')
    {
        for ($i = 1; $i <= $count; $i++) {
            $structure = new DefaultStructureCache();
            $structure->setUuid($webspaceName . $i);
            $structure->setWebspaceKey($webspaceName);
            $structure->getProperty('title')->setValue('Structure Title ' . $i);

            $structure->getProperty('url')->setValue('/');
            $structure->setNodeState(StructureInterface::STATE_PUBLISHED);
            $structure->setLanguageCode('de');

            $this->getSearchManager()->index($structure);
        }
    }

    public function indexStructure($title, $url)
    {
        $data = array(
            'title' => $title,
            'url' => $url
        );

        /** @var ContentMapperInterface $mapper */
        $mapper = $this->getContainer()->get('sulu.content.mapper');
        $structure = $mapper->save($data, 'default', 'sulu_io', 'de', 1, true, null, null, Structure::STATE_PUBLISHED);

        return $structure;
    }
}
