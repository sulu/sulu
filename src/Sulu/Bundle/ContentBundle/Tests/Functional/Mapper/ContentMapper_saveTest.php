<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Mapper;

use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\StructureInterface;
use DTL\Bundle\ContentBundle\Tests\Integration\BaseTestCase;
use DTL\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Structure;
use DTL\Component\Content\Document\PageInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;

class ContentMapper_saveTest extends SuluTestCase
{
    private $contentMapper;
    private $documentManager;

    public function setUp()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('dtl_content.compat.content_mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager');
        $this->parent = $this->documentManager->find('/cmf/sulu_io/contents');
    }

    public function provideSave()
    {
        return array(
            array(
                ContentMapperRequest::create('page')
                    ->setTemplateKey('contact')
                    ->setWebspaceKey('sulu_io')
                    ->setUserId(1)
                    ->setState(WorkflowStage::PUBLISHED)
                    ->setLocale('en')
                    ->setData(array(
                        'title' => 'This is a test',
                        'url' => '/url/to/content',
                        'name' => 'Daniel Leech',
                        'email' => 'daniel@dantleech.com',
                        'telephone' => '123123',
                    ))
            ),
        );
    }

    /**
     * Can save without crashing
     *
     * @dataProvider provideSave
     */
    public function testSave($request)
    {
        $this->contentMapper->saveRequest($request);
    }

    /**
     * Can update a node in a different locale
     */
    public function testSaveUpdate()
    {
        $englishContent = array(
            'title' => 'This is a test',
            'url' => '/url/to/content',
            'name' => 'Daniel Leech',
            'email' => 'daniel@dantleech.com',
            'telephone' => '123123',
        );

        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('en')
            ->setData($englishContent);

        $structure = $this->contentMapper->saveRequest($request);

        $frenchContent = array(
            'title' => 'Ceci est une test',
            'url' => '/url/to/content',
            'name' => 'Danièl le Français',
            'email' => 'daniel@dantleech.com',
            'telephone' => '123123',
        );

        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setUuid($structure->getUuid())
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('de')
            ->setData($frenchContent);
        $structure = $this->contentMapper->saveRequest($request);

        unset($englishContent['title'], $englishContent['url']);
        unset($frenchContent['title'], $frenchContent['url']);

        $document = $this->documentManager->findTranslation(null, $structure->getUuid(), 'en');
        $this->assertEquals($englishContent, $document->getContent()->getArrayCopy());

        $document = $this->documentManager->findTranslation(null, $structure->getUuid(), 'de');
        $this->assertEquals($frenchContent, $document->getContent()->getArrayCopy());
    }

    /**
     * Can save a document with an assigned parent
     */
    public function testSaveParent()
    {
        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('en')
            ->setData(array(
                'title' => 'This is a test',
                'url' => '/url/to/content',
                'name' => 'Daniel Leech',
                'email' => 'daniel@dantleech.com',
                'telephone' => '123123',
            ));

        $structure = $this->contentMapper->saveRequest($request);

        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setParentUuid($structure->getUuid())
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('de')
            ->setData(array(
                'title' => 'Ceci est une test',
                'url' => '/url/to/content',
                'name' => 'Danièl le Français',
                'email' => 'daniel@dantleech.com',
                'telephone' => '123123',
            ));
        $this->contentMapper->saveRequest($request);

        $leafDocument = $this->documentManager->find(null, $path = '/cmf/sulu_io/contents/this-is-a-test/ceci-est-une-test');
        $this->assertNotNull($leafDocument, $path . ' exists');

        // now when we update this document we can leave parent as NULL
        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setUuid($leafDocument->getUuid())
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('de')
            ->setData(array(
                'title' => 'Bonjour le monde',
                'url' => '/url/to/content',
                'name' => 'Danièl le Français',
                'email' => 'daniel@dantleech.com',
                'telephone' => '123123',
            ));
        $this->contentMapper->saveRequest($request);

        $leafDocument = $this->documentManager->find(null, '/cmf/sulu_io/contents/this-is-a-test/bonjour-le-monde');
        $this->assertNotNull($leafDocument, 'Updating existing document no parent specified');
        $this->assertEquals('Bonjour le monde', $leafDocument->getTitle());
    }

    /**
     * Can save the start page using the dedicated ContentMapper
     * saveStartPage method.
     */
    public function testSaveStartPage()
    {
        // this is actually the data sent for mapping ...
        $data = json_decode('
            {
                "_embedded": {
                    "nodes": []
                },
                "_links": {
                    "children": "/admin/api/nodes?parent=8f817a80-d48f-4181-9319-96ecc9ad33b6&depth=1&webspace=sulu_io&language=de",
                    "self": "/admin/api/nodes/8f817a80-d48f-4181-9319-96ecc9ad33b6"
                },
                "changed": "2015-03-10T13:59:16+0100",
                "concreteLanguages": [
                    "en",
                    "de"
                ],
                "created": "2015-03-10T13:59:16+0100",
                "enabledShadowLanguages": [],
                "hasSub": false,
                "id": "index",
                "internal": false,
                "navigation": true,
                "nodeState": 2,
                "nodeType": 1,
                "originTemplate": "prototype",
                "path": "/cmf/sulu_io/contents",
                "published": "2015-03-10T13:59:16+0100",
                "shadowBaseLanguage": false,
                "shadowOn": false,
                "template": "contact",
                "title": "asdHomepage",
                "url": "/"
            }
        ', true);

        $this->contentMapper->saveStartPage(
            $data,
            'contact',
            'sulu_io',
            'en',
            1
        );
    }

    /**
     * Can save set the redirect type to internal and specify
     * an internal link target.
     */
    public function testSaveRedirectInternal()
    {
        $target = $this->saveTestPageWithData(array(
            'title' => 'Hello world',
            'url' => '/hello',
        ));

        $structure = $this->saveTestPageWithData(array(
            'title' => 'My redirect',
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
            'internal_link' => $target->getUuid(),
            'url' => '/redirect',
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find(null, $structure->getUuid());

        $this->assertEquals(PageInterface::REDIRECT_TYPE_INTERNAL, $document->getRedirectType());
        $this->assertInstanceOf('DTL\Component\Content\Document\DocumentInterface', $document->getRedirectTarget());
        $this->assertEquals($target->getUuid(), $document->getRedirectTarget()->getUuid());
    }

    /**
     * Can save set the redirect type to external and specify
     * an external link target.
     */
    public function testSaveRedirectExternal()
    {
        $structure = $this->saveTestPageWithData(array(
            'title' => 'My redirect',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK,
            'external' => 'http://www.dantleech.com',
            'url' => '/redirect',
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find(null, $structure->getUuid());

        $this->assertEquals(PageInterface::REDIRECT_TYPE_EXTERNAL, $document->getRedirectType());
        $this->assertEquals('http://www.dantleech.com', $document->getRedirectExternal());
    }

    /**
     * Can save a shadow page
     */
    public function testSaveShadow()
    {
        $structure = $this->saveTestPageWithData(array(
            'title' => 'Shadow',
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
            'url' => '/shadow',
            'shadowOn' => true,
            'shadowBaseLanguage' => 'fr',
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find(null, $structure->getUuid());

        $this->assertTrue($document->getShadowLocaleEnabled());
        $this->assertEquals('fr', $document->getShadowLocale());
    }

    /**
     * Can set navigation contexts
     */
    public function testSaveNavigationContexts()
    {
        $structure = $this->saveTestPageWithData(array(
            'title' => 'Navigation',
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
            'url' => '/navigation',
            'navContexts' => array('footer', 'navigation'),
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find(null, $structure->getUuid());

        $this->assertEquals(array('footer', 'navigation'), $document->getNavigationContexts());
    }

    /**
     * Create a simple page with the given data
     */
    private function saveTestPageWithData($data)
    {
        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('de')
            ->setData($data);
        return $this->contentMapper->saveRequest($request);
    }
}
