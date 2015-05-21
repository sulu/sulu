<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Search;

use Sulu\Bundle\ContentBundle\Tests\Fixtures\DefaultStructureCache;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\Filesystem\Filesystem;

class BaseTestCase extends SuluTestCase
{
    protected $session;
    protected $contentMapper;

    public function setUp()
    {
        $this->initPhpcr();
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/../app/data');

        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
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
            'url' => $url,
        );

        /** @var ContentMapperInterface $mapper */
        $structure = $this->contentMapper->save($data, 'default', 'sulu_io', 'de', 1, true, null, null, Structure::STATE_PUBLISHED);

        return $structure;
    }
}
