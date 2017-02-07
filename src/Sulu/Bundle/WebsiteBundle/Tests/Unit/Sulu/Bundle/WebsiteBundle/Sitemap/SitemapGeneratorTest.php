<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Webspace;

class SitemapGeneratorTest extends SuluTestCase
{
    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var SitemapGeneratorInterface
     */
    private $sitemapGenerator;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var NodeInterface
     */
    private $contents;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->extensionManager = $this->getContainer()->get('sulu_content.extension.manager');
        $this->languageNamespace = $this->getContainer()->getParameter('sulu.content.language.namespace');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->prepareTestData('en');
        $this->prepareTestData('en_us');

        $this->contents = $this->session->getNode('/cmf/test_io/contents');

        $this->contents->setProperty('i18n:en-state', Structure::STATE_PUBLISHED);
        $this->contents->setProperty('i18n:en-nodeType', Structure::NODE_TYPE_CONTENT);
        $this->session->save();

        $contentQuery = new ContentQueryExecutor(
            $this->sessionManager,
            $this->mapper
        );

        $this->sitemapGenerator = new SitemapGenerator(
            $contentQuery,
            $this->webspaceManager,
            new SitemapContentQueryBuilder($this->structureManager, $this->extensionManager, $this->languageNamespace)
        );
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager !== null) {
            return;
        }

        $this->webspace = new Webspace();
        $this->webspace->setKey('test_io');

        $local1 = new Localization();
        $local1->setLanguage('en');

        $local2 = new Localization();
        $local2->setLanguage('en');
        $local2->setCountry('us');

        $this->webspace->setLocalizations([$local1, $local2]);
        $this->webspace->setName('Default');

        $this->webspace->addDefaultTemplate('page', 'default');
        $this->webspace->setTheme('test');

        $this->webspace->setNavigation(
            new Navigation(
                [
                    new NavigationContext('main', []),
                    new NavigationContext('footer', []),
                ]
            )
        );

        $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
        $this->webspaceManager
            ->expects($this->any())
            ->method('findWebspaceByKey')
            ->will($this->returnValue($this->webspace));
    }

    public function getExtensionCallback()
    {
        return new ExcerptStructureExtension($this->structureManager, $this->contentTypeManager);
    }

    public function getExtensionsCallback()
    {
        return [$this->getExtensionCallback()];
    }

    /**
     * @param string $locale
     *
     * @return StructureInterface[]
     */
    private function prepareTestData($locale)
    {
        // TODO set published state?
        /** @var PageDocument $newsDocument */
        $newsDocument = $this->documentManager->create('page');
        $newsDocument->setStructureType('overview');
        $newsDocument->setTitle('News ' . $locale);
        $newsDocument->setResourceSegment('/news');
        $newsDocument->setNavigationContexts(['footer']);
        $newsDocument->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($newsDocument, $locale, ['parent_path' => '/cmf/test_io/contents']);
        $this->documentManager->publish($newsDocument, $locale);
        $this->documentManager->flush();

        /** @var PageDocument $productDocument */
        $productDocument = $this->documentManager->create('page');
        $productDocument->setStructureType('overview');
        $productDocument->setTitle('Products ' . $locale);
        $productDocument->setResourceSegment('/products');
        $productDocument->setNavigationContexts(['main']);
        $productDocument->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($productDocument, $locale, ['parent_path' => '/cmf/test_io/contents']);
        $this->documentManager->flush();

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle('News-1 ' . $locale);
        $document->setResourceSegment('/news/news-1');
        $document->setNavigationContexts(['main', 'footer']);
        $document->setParent($newsDocument);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, $locale);
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();

        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle('News-2 ' . $locale);
        $document->setResourceSegment('/news/news-2');
        $document->setNavigationContexts(['main']);
        $document->setParent($newsDocument);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, $locale);
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();

        $document = $this->documentManager->create('page');
        $document->setStructureType('overview');
        $document->setTitle('Products-1 ' . $locale);
        $document->setResourceSegment('/products/product-1');
        $document->setRedirectType(RedirectType::INTERNAL);
        $document->setRedirectTarget($productDocument);
        $document->setNavigationContexts(['main', 'footer']);
        $document->setParent($productDocument);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, $locale);
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();

        $document = $this->documentManager->create('page');
        $document->setStructureType('overview');
        $document->setTitle('Products-2 ' . $locale);
        $document->setResourceSegment('/products/product-w');
        $document->setRedirectType(RedirectType::EXTERNAL);
        $document->setRedirectExternal('http://www.asdf.at');
        $document->setNavigationContexts(['main']);
        $document->setParent($productDocument);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, $locale);
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();

        $document = $this->documentManager->create('page');
        $document->setStructureType('overview');
        $document->setRedirectTarget('overview');
        $document->setTitle('Products-3 ' . $locale);
        $document->setResourceSegment('/products/product-3');
        $document->setRedirectType(RedirectType::INTERNAL);
        $document->setRedirectTarget($newsDocument);
        $document->setNavigationContexts(['main']);
        $document->setParent($productDocument);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, $locale);
        $this->documentManager->publish($document, $locale);
        $this->documentManager->flush();
    }

    public function testGenerateAllFlat()
    {
        $result = $this->sitemapGenerator->generateAllLocals('test_io', true)->getSitemap();

        $result = array_map(
            function ($item) {
                return [$item['title'], $item['url'], $item['nodeType']];
            },
            $result
        );

        $this->assertCount(12, $result);
        $this->assertContains(['Homepage', '/', 1], $result);
        $this->assertContains(['News en', '/news', 1], $result);
        $this->assertContains(['News-1 en', '/news/news-1', 1], $result);
        $this->assertContains(['News-2 en', '/news/news-2', 1], $result);
        $this->assertContains(['Products-2 en', 'http://www.asdf.at', 4], $result);
        $this->assertContains(['Products-3 en', '/news', 2], $result);
        $this->assertContains(['News en_us', '/news', 1], $result);
        $this->assertContains(['News-1 en_us', '/news/news-1', 1], $result);
        $this->assertContains(['News-2 en_us', '/news/news-2', 1], $result);
        // Products-1 en/en_us is a internal link to the unpublished page products (not in result)
        $this->assertContains(['Products-2 en_us', 'http://www.asdf.at', 4], $result);
        $this->assertContains(['Products-3 en_us', '/news', 2], $result);
    }

    public function testGenerateFlat()
    {
        $result = $this->sitemapGenerator->generate('test_io', 'en', true)->getSitemap();

        $this->assertCount(6, $result);
        $this->assertEquals('Homepage', $result[0]['title']);
        $this->assertEquals('News en', $result[1]['title']);
        $this->assertEquals('News-1 en', $result[2]['title']);
        $this->assertEquals('News-2 en', $result[3]['title']);
        $this->assertEquals('Products-2 en', $result[4]['title']);
        $this->assertEquals('Products-3 en', $result[5]['title']);

        $this->assertEquals('/', $result[0]['url']);
        $this->assertEquals('/news', $result[1]['url']);
        $this->assertEquals('/news/news-1', $result[2]['url']);
        $this->assertEquals('/news/news-2', $result[3]['url']);
        $this->assertEquals('http://www.asdf.at', $result[4]['url']);
        $this->assertEquals('/news', $result[5]['url']);

        $this->assertEquals(1, $result[0]['nodeType']);
        $this->assertEquals(1, $result[1]['nodeType']);
        $this->assertEquals(1, $result[2]['nodeType']);
        $this->assertEquals(1, $result[3]['nodeType']);
        $this->assertEquals(4, $result[4]['nodeType']);
        $this->assertEquals(2, $result[5]['nodeType']);
    }

    public function testGenerateTree()
    {
        $result = $this->sitemapGenerator->generate('test_io', 'en')->getSitemap();

        $root = $result;
        $this->assertEquals('Homepage', $root['title']);
        $this->assertEquals('/', $root['url']);
        $this->assertEquals(1, $root['nodeType']);

        $layer1 = array_values($root['children']);

        $this->assertCount(3, $layer1);

        $this->assertEquals('News en', $layer1[0]['title']);
        $this->assertEquals('/news', $layer1[0]['url']);
        $this->assertEquals(1, $layer1[0]['nodeType']);

        $this->assertEquals('Products-2 en', $layer1[1]['title']);
        $this->assertEquals(4, $layer1[1]['nodeType']);
        $this->assertEquals('http://www.asdf.at', $layer1[1]['url']);

        $this->assertEquals('Products-3 en', $layer1[2]['title']);
        $this->assertEquals('/news', $layer1[2]['url']);
        $this->assertEquals(2, $layer1[2]['nodeType']);

        $layer21 = array_values($layer1[0]['children']);

        $this->assertEquals('News-1 en', $layer21[0]['title']);
        $this->assertEquals('/news/news-1', $layer21[0]['url']);
        $this->assertEquals(1, $layer21[0]['nodeType']);

        $this->assertEquals('News-2 en', $layer21[1]['title']);
        $this->assertEquals('/news/news-2', $layer21[1]['url']);
        $this->assertEquals(1, $layer21[1]['nodeType']);
    }
}

class ExcerptStructureExtension extends AbstractExtension
{
    /**
     * name of structure extension.
     */
    const EXCERPT_EXTENSION_NAME = 'excerpt';

    /**
     * will be filled with data in constructor
     * {@inheritdoc}
     */
    protected $properties = [];

    /**
     * {@inheritdoc}
     */
    protected $name = self::EXCERPT_EXTENSION_NAME;

    /**
     * {@inheritdoc}
     */
    protected $additionalPrefix = self::EXCERPT_EXTENSION_NAME;

    /**
     * @var StructureInterface
     */
    protected $excerptStructure;

    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var string
     */
    private $languageNamespace;

    public function __construct(
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        foreach ($this->excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (isset($data[$property->getName()])) {
                $property->setValue($data[$property->getName()]);
                $contentType->write(
                    $node,
                    new TranslatedProperty(
                        $property,
                        $languageCode . '-' . $this->additionalPrefix,
                        $this->languageNamespace
                    ),
                    null, // userid
                    $webspaceKey,
                    $languageCode,
                    null // segmentkey
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        $data = [];
        foreach ($this->excerptStructure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $contentType->read(
                $node,
                new TranslatedProperty(
                    $property,
                    $languageCode . '-' . $this->additionalPrefix,
                    $this->languageNamespace
                ),
                $webspaceKey,
                $languageCode,
                null // segmentkey
            );
            $data[$property->getName()] = $contentType->getContentData($property);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguageCode($languageCode, $languageNamespace, $namespace)
    {
        // lazy load excerpt structure to avoid redeclaration of classes
        // should be done before parent::setLanguageCode because it uses the $thi<->properties
        // which will be set in initExcerptStructure
        if ($this->excerptStructure === null) {
            $this->excerptStructure = $this->initExcerptStructure();
        }

        parent::setLanguageCode($languageCode, $languageNamespace, $namespace);
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * initiates structure and properties.
     */
    private function initExcerptStructure()
    {
        $excerptStructure = $this->structureManager->getStructure(self::EXCERPT_EXTENSION_NAME);
        /** @var PropertyInterface $property */
        foreach ($excerptStructure->getProperties() as $property) {
            $this->properties[] = $property->getName();
        }

        return $excerptStructure;
    }
}
