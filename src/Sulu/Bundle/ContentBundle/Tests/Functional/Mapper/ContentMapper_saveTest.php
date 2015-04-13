<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Mapper;

use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Structure;
use DTL\Component\Content\Document\PageInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Bundle\ContentBundle\Document\PageDocument;

class ContentMapper_saveTest extends SuluTestCase
{
    private $contentMapper;
    private $documentManager;

    public function setUp()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager');
        $this->parent = $this->documentManager->find('/cmf/sulu_io/contents', 'de');
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
    public function testContentSaveUpdate()
    {
        $englishContent = array(
            'title' => 'This is a test',
            'name' => 'Daniel Leech',
            'email' => 'daniel@dantleech.com',
            'telephone' => '123123',
            'information' => 'Hello',
            'smart-content' => 'This is smart',
            'url' => '/path/to',
        );

        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('en')
            ->setData($englishContent);

        $document = $this->contentMapper->saveRequest($request);

        $frenchContent = array(
            'title' => 'Ceci est une test',
            'name' => 'Danièl le Français',
            'email' => 'daniel@dantleech.com',
            'telephone' => '123123',
            'information' => 'Hello',
            'smart-content' => 'This is smart',
            'url' => '/path/to',
        );

        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setUuid($document->getUuid())
            ->setUserId(1)
            ->setState(WorkflowStage::PUBLISHED)
            ->setLocale('de')
            ->setData($frenchContent);
        $document = $this->contentMapper->saveRequest($request);

        $document = $this->documentManager->find($document->getUuid(), 'en');
        $this->assertEquals($englishContent, $document->getContent()->getArrayCopy());

        $document = $this->documentManager->find($document->getUuid(), 'de');
        $this->assertEquals($frenchContent, $document->getContent()->getArrayCopy());
    }

    /**
     * Can save a document with an assigned parent
     */
    public function testContentSaveParent()
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

        $document = $this->contentMapper->saveRequest($request);

        $request = ContentMapperRequest::create('page')
            ->setTemplateKey('contact')
            ->setWebspaceKey('sulu_io')
            ->setParentUuid($document->getUuid())
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

        $leafDocument = $this->documentManager->find($path = '/cmf/sulu_io/contents/this-is-a-test/ceci-est-une-test');
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

        $leafDocument = $this->documentManager->find('/cmf/sulu_io/contents/this-is-a-test/ceci-est-une-test');
        $this->assertNotNull($leafDocument, 'Updating existing document no parent specified');
        $this->assertEquals('Bonjour le monde', $leafDocument->getTitle());
    }

    /**
     * Can save the start page using the dedicated ContentMapper
     * saveStartPage method.
     */
    public function testContentSaveStartPage()
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

        $document = $this->saveTestPageWithData(array(
            'title' => 'My redirect',
            'nodeType' => RedirectType::INTERNAL,
            'internal_link' => $target->getUuid(),
            'url' => '/redirect',
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find($document->getUuid(), 'de');

        $this->assertEquals(RedirectType::INTERNAL, $document->getRedirectType());
        $this->assertInstanceOf(PageDocument::class, $document->getRedirectTarget());
        $this->assertEquals($target->getUuid(), $document->getRedirectTarget()->getUuid());
    }

    /**
     * Can save set the redirect type to external and specify
     * an external link target.
     */
    public function testSaveRedirectExternal()
    {
        $document = $this->saveTestPageWithData(array(
            'title' => 'My redirect',
            'nodeType' => RedirectType::EXTERNAL,
            'external' => 'http://www.dantleech.com',
            'url' => '/redirect',
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find($document->getUuid(), 'de');

        $this->assertEquals(RedirectType::EXTERNAL, $document->getRedirectType());
        $this->assertEquals('http://www.dantleech.com', $document->getRedirectExternal());
    }

    /**
     * Can save a shadow page
     */
    public function testSaveShadow()
    {
        $document = $this->saveTestPageWithData(array(
            'title' => 'Shadow',
            'nodeType' => RedirectType::INTERNAL,
            'url' => '/shadow',
            'shadowOn' => true,
            'shadowBaseLanguage' => 'fr',
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find($document->getUuid(), 'de');

        $this->assertTrue($document->isShadowLocaleEnabled());
        $this->assertEquals('fr', $document->getShadowLocale());
    }

    /**
     * Can set navigation contexts
     */
    public function testSaveNavigationContexts()
    {
        $document = $this->saveTestPageWithData(array(
            'title' => 'Navigation',
            'nodeType' => RedirectType::INTERNAL,
            'url' => '/navigation',
            'navContexts' => array('footer', 'navigation'),
        ));

        $this->documentManager->clear();
        $document = $this->documentManager->find($document->getUuid(), 'de');

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
