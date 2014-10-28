<?php

namespace Sulu\Component\Content\Mapper;

use Sulu\Component\Content\Structure;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\StructureInterface;

class ContentMapperSnippetTest extends SuluTestCase
{
    private $contentMapper;
    private $session;

    private $snippet1;
    private $snippet1OriginalPath;

    public function setUp()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->loadFixtures();
    }

    public function loadFixtures()
    {
        $req = ContentMapperRequest::create()
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('animal')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'ElePHPant',
            ))
            ->setState(StructureInterface::STATE_PUBLISHED);

        $this->snippet1 = $this->contentMapper->saveRequest($req);
        $this->snippet1OriginalPath = $this->session->getNodeByIdentifier($this->snippet1->getUuid())->getPath();
    }

    public function testChangeSnippetTemplate()
    {
        $req = ContentMapperRequest::create()
            ->setUuid($this->snippet1->getUuid())
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setUserId(1)
            ->setData(array(
                'title' => 'ElePHPant',
            ));
        $this->contentMapper->saveRequest($req);

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false);
        } catch (\PHPCR\PathNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->session->getNode('/cmf/snippets/hotel/elephpant');
    }
}
