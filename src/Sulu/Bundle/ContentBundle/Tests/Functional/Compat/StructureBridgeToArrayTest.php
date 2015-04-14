<?php

namespace DTL\Bundle\ContentBundle\Tests\Integration\Compat;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Compat\StructureInterface;
use PHPCR\Util\NodeHelper;
use DTL\Bundle\ContentBundle\Tests\Integration\BaseTestCase;

class StructureBridgeToArrayTest extends SuluTestCase
{
    private $manager;

    public function setUp()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->manager = $this->getContainer()->get('sulu_document_manager');
    }

    public function testHomepage()
    {
        $startDocument = $this->manager->find('/cmf/sulu_io/contents', 'en');
        $startPage = $this->contentMapper->loadStartPage('sulu_io', 'en');

        $expected = array(
            'id' => $startDocument->getUuid(),
            'enabledShadowLanguages' => array(),
            'nodeType' => 1,
            'internal' => false,
            'shadowOn' => false,
            'shadowBaseLanguage' => false,
            'concreteLanguages' => array ('en', 'de'),
            'template' => 'overview',
            'hasSub' => false,
            'creator' => null,
            'changer' => null,
            'created' => $startDocument->getCreated(),
            'changed' => $startDocument->getChanged(),
            'title' => 'Homepage',
            'url' => '/',
            'path' => '/cmf/sulu_io/contents',
            'nodeState' => 2,
            'originTemplate' => 'overview',
            'published' => $startDocument->getPublished(),
            'publishedState' => true,
            'navContexts' => array(),
            'linked' => null,
        );

        $this->assertEquals($expected, $startPage->toArray());
    }
}
