<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class NavigationTest extends SuluTestCase
{
    /**
     * @var StructureInterface[]
     */
    private $data;

    /**
     * @var NavigationMapperInterface
     */
    private $navigation;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var string
     */
    private $languageNamespace;

    private $homeDocument;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->extensionManager = $this->getContainer()->get('sulu_content.extension.manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->languageNamespace = 'i18n';
        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->data = $this->prepareTestData();

        $contentQuery = new ContentQueryExecutor($this->sessionManager, $this->mapper);

        $this->navigation = new NavigationMapper(
            $this->mapper,
            $contentQuery,
            new NavigationQueryBuilder($this->structureManager, $this->extensionManager, $this->languageNamespace),
            $this->sessionManager
        );
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareTestData()
    {
        $documents = [];

        $documents['news'] = $this->createPageDocument();
        $documents['news']->setStructureType('simple');
        $documents['news']->setParent($this->homeDocument);
        $documents['news']->setTitle('News');
        $documents['news']->setResourceSegment('/news');
        $documents['news']->setExtensionsData(['excerpt' => ['title' => 'Excerpt News']]);
        $documents['news']->setNavigationContexts(['footer']);
        $documents['news']->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($documents['news'], 'en');
        $this->documentManager->publish($documents['news'], 'en');
        $this->documentManager->flush();

        $documents['news/news-1'] = $this->createPageDocument();
        $documents['news/news-1']->setStructureType('simple');
        $documents['news/news-1']->setParent($documents['news']);
        $documents['news/news-1']->setTitle('News-1');
        $documents['news/news-1']->setResourceSegment('/news/news-1');
        $documents['news/news-1']->setExtensionsData(['excerpt' => ['title' => 'Excerpt News 1']]);
        $documents['news/news-1']->setNavigationContexts(['main', 'footer']);
        $documents['news/news-1']->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($documents['news/news-1'], 'en');
        $this->documentManager->publish($documents['news/news-1'], 'en');
        $this->documentManager->flush();

        $documents['news/news-2'] = $this->createPageDocument();
        $documents['news/news-2']->setStructureType('simple');
        $documents['news/news-2']->setParent($documents['news']);
        $documents['news/news-2']->setTitle('News-2');
        $documents['news/news-2']->setResourceSegment('/news/news-2');
        $documents['news/news-2']->setExtensionsData(['excerpt' => ['title' => 'Excerpt News 2']]);
        $documents['news/news-2']->setNavigationContexts(['main']);
        $documents['news/news-2']->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($documents['news/news-2'], 'en');
        $this->documentManager->publish($documents['news/news-2'], 'en');
        $this->documentManager->flush();

        $documents['products'] = $this->createPageDocument();
        $documents['products']->setStructureType('simple');
        $documents['products']->setParent($this->homeDocument);
        $documents['products']->setTitle('Products');
        $documents['products']->setResourceSegment('/products');
        $documents['products']->setExtensionsData(['excerpt' => ['title' => 'Excerpt Products']]);
        $documents['products']->setNavigationContexts(['main']);
        $documents['products']->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($documents['products'], 'en');
        $this->documentManager->publish($documents['products'], 'en');
        $this->documentManager->flush();

        $documents['products/product-1'] = $this->createPageDocument();
        $documents['products/product-1']->setStructureType('simple');
        $documents['products/product-1']->setParent($documents['products']);
        $documents['products/product-1']->setTitle('Products-1');
        $documents['products/product-1']->setResourceSegment('/products/products-1');
        $documents['products/product-1']->setExtensionsData(['excerpt' => ['title' => 'Excerpt Products 1']]);
        $documents['products/product-1']->setNavigationContexts(['main', 'footer']);
        $documents['products/product-1']->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($documents['products/product-1'], 'en');
        $this->documentManager->publish($documents['products/product-1'], 'en');
        $this->documentManager->flush();

        $documents['products/product-2'] = $this->createPageDocument();
        $documents['products/product-2']->setStructureType('simple');
        $documents['products/product-2']->setParent($documents['products']);
        $documents['products/product-2']->setTitle('Products-2');
        $documents['products/product-2']->setResourceSegment('/products/products-2');
        $documents['products/product-2']->setExtensionsData(['excerpt' => ['title' => 'Excerpt Products 2']]);
        $documents['products/product-2']->setNavigationContexts(['main']);
        $documents['products/product-2']->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($documents['products/product-2'], 'en');
        $this->documentManager->publish($documents['products/product-2'], 'en');
        $this->documentManager->flush();

        return $documents;
    }

    public function testMainNavigation()
    {
        $main = $this->navigation->getRootNavigation('sulu_io', 'en', 2);
        $this->assertEquals(2, count($main));
        $this->assertEquals(2, count($main[0]['children']));
        $this->assertEquals(2, count($main[1]['children']));

        $this->assertEquals('/news', $main[0]['url']);
        $this->assertEquals('/news/news-1', $main[0]['children'][0]['url']);
        $this->assertEquals('/news/news-2', $main[0]['children'][1]['url']);
        $this->assertEquals('/products', $main[1]['url']);
        $this->assertEquals('/products/products-1', $main[1]['children'][0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['children'][1]['url']);

        $main = $this->navigation->getRootNavigation('sulu_io', 'en', 1);
        $this->assertEquals(2, count($main));
        $this->assertEquals(0, count($main[0]['children']));
        $this->assertEquals(0, count($main[1]['children']));

        $main = $this->navigation->getRootNavigation('sulu_io', 'en', null);
        $this->assertEquals(2, count($main));
        $this->assertEquals(2, count($main[0]['children']));
        $this->assertEquals(2, count($main[1]['children']));
        $this->assertEquals(0, count($main[0]['children'][0]['children']));
        $this->assertEquals(0, count($main[0]['children'][1]['children']));
        $this->assertEquals(0, count($main[1]['children'][0]['children']));
        $this->assertEquals(0, count($main[1]['children'][1]['children']));
    }

    public function testNavigation()
    {
        $main = $this->navigation->getNavigation($this->data['news']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(2, count($main));
        $this->assertEquals(0, count($main[0]['children']));
        $this->assertEquals(0, count($main[1]['children']));

        $this->assertEquals($this->data['news/news-1']->getUuid(), $main[0]['uuid']);
        $this->assertEquals('News-1', $main[0]['title']);
        $this->assertEquals('/news/news-1', $main[0]['url']);

        $this->assertEquals($this->data['news/news-2']->getUuid(), $main[1]['uuid']);
        $this->assertEquals('News-2', $main[1]['title']);
        $this->assertEquals('/news/news-2', $main[1]['url']);
    }

    public function testMainNavigationFlat()
    {
        $result = $this->navigation->getRootNavigation('sulu_io', 'en', 1, true);
        $this->assertEquals(2, count($result));
        $this->assertEquals('News', $result[0]['title']);
        $this->assertEquals('Products', $result[1]['title']);

        $this->markTestSkipped('This method does not work at more than one level. See issue #1252');

        $result = $this->navigation->getRootNavigation('sulu_io', 'en', 2, true);
        $this->assertEquals(6, count($result));
        $this->assertEquals('News', $result[0]['title']);
        $this->assertEquals('News-1', $result[1]['title']);

        $this->assertEquals('News-2', $result[2]['title']);
        $this->assertEquals('Products', $result[3]['title']);
        $this->assertEquals('Products-1', $result[4]['title']);
        $this->assertEquals('Products-2', $result[5]['title']);
    }

    public function testNavigationFlat()
    {
        $this->markTestSkipped('This method does not work at more than one level. See issue #1252');

        $document = $this->createPageDocument();
        $document->setStructureType('simple');
        $document->setTitle('SubNews');
        $document->setResourceSegment('/asdf');
        $document->setNavigationContexts(['footer']);
        $document->setParent($this->data['news/news-1']);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->flush();

        $result = $this->navigation->getNavigation($this->data['news']->getUuid(), 'sulu_io', 'en', 2, true);
        $this->assertEquals(3, count($result));
        $this->assertEquals('News-1', $result[0]['title']);
        $this->assertEquals('News-2', $result[1]['title']);
        $this->assertEquals('SubNews', $result[2]['title']);
    }

    public function testNavigationExcerpt()
    {
        $document = $this->createPageDocument();
        $document->setStructureType('simple');
        $document->setTitle('SubNews');
        $document->setResourceSegment('/asdf');
        $document->setExtensionsData(['excerpt' => ['title' => 'Excerpt Products 2']]);
        $document->setNavigationContexts(['footer']);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setParent($this->data['news/news-1']);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();

        $result = $this->navigation->getNavigation(
            $this->data['news']->getUuid(),
            'sulu_io',
            'en',
            2,
            true,
            null,
            true
        );
        $this->assertEquals(3, count($result));
        $this->markTestSkipped('This method does not work at more than one level. See issue #1252');

        $this->assertEquals('News-1', $result[0]['title']);
        $this->assertEquals('Excerpt News 1', $result[0]['excerpt']['title']);

        $this->assertEquals('News-2', $result[1]['title']);
        $this->assertEquals('Excerpt News 2', $result[1]['excerpt']['title']);

        $this->assertEquals('SubNews', $result[2]['title']);
        $this->assertEquals('', $result[2]['excerpt']['title']);
    }

    public function testBreadcrumb()
    {
        $breadcrumb = $this->navigation->getBreadcrumb($this->data['news/news-2']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(3, count($breadcrumb));

        // startpage has no title
        $this->assertEquals('Homepage', $breadcrumb[0]->getTitle());
        $this->assertEquals('/', $breadcrumb[0]->getUrl());
        $this->assertEquals('News', $breadcrumb[1]->getTitle());
        $this->assertEquals('/news', $breadcrumb[1]->getUrl());
        $this->assertEquals('News-2', $breadcrumb[2]->getTitle());
        $this->assertEquals('/news/news-2', $breadcrumb[2]->getUrl());
    }

    public function testNavContexts()
    {
        // context footer (only news and one sub page news-1)
        $result = $this->navigation->getRootNavigation('sulu_io', 'en', 2, false, 'footer');

        $this->assertEquals(1, count($result));
        $layer1 = $result;

        $this->assertEquals(1, count($layer1[0]['children']));
        $layer2 = $layer1[0]['children'][0];

        $this->assertEquals('News', $layer1[0]['title']);
        $this->assertEquals('News-1', $layer2['title']);

        // /products/product-1 not: because of missing nav context on /products

        // context main (only products and two sub pages
        $result = $this->navigation->getRootNavigation('sulu_io', 'en', 2, false, 'main');

        $this->assertEquals(1, count($result));
        $layer1 = $result;

        $this->assertEquals(2, count($layer1[0]['children']));

        // /news/news-1 and /news/news-2 not: because of missing nav context on /news

        $this->assertEquals('Products', $layer1[0]['title']);

        $layer2 = $layer1[0]['children'];

        $this->assertEquals('Products-1', $layer2[0]['title']);
        $this->assertEquals('Products-2', $layer2[1]['title']);
    }

    public function testNavContextsFlat()
    {
        // context footer (only news and one sub page news-1)
        $result = $this->navigation->getRootNavigation('sulu_io', 'en', 2, true, 'footer');

        $this->assertEquals(3, count($result));

        $this->markTestSkipped('This method does not work at more than one level. See issue #1252');

        // check children
        $this->assertEquals(0, count($result[0]['children']));
        $this->assertEquals(0, count($result[1]['children']));
        $this->assertEquals(0, count($result[2]['children']));

        // check title
        $this->assertEquals('News', $result[0]['title']);
        $this->assertEquals('News-1', $result[1]['title']);
        $this->assertEquals('Products-1', $result[2]['title']);

        // context main (only products and two sub pages
        $result = $this->navigation->getRootNavigation('sulu_io', 'en', 2, true, 'main');

        $this->assertEquals(5, count($result));

        // check children
        $this->assertEquals(0, count($result[0]['children']));
        $this->assertEquals(0, count($result[1]['children']));
        $this->assertEquals(0, count($result[2]['children']));
        $this->assertEquals(0, count($result[3]['children']));
        $this->assertEquals(0, count($result[4]['children']));

        // check title
        $this->assertEquals('News-1', $result[0]['title']);
        $this->assertEquals('News-2', $result[1]['title']);
        $this->assertEquals('Products', $result[2]['title']);
        $this->assertEquals('Products-1', $result[3]['title']);
        $this->assertEquals('Products-2', $result[4]['title']);
    }

    public function testNavigationTestPage()
    {
        $document = $this->createPageDocument();
        $document->setStructureType('simple');
        $document->setTitle('Products-3');
        $document->setResourceSegment('/products/products-3');
        $document->setParent($this->data['products']);
        $document->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->flush();

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(2, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);

        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(3, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);
        $this->assertEquals('/products/products-3', $main[2]['url']);

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1, false, 'main');
        $this->assertEquals(2, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);

        $document->setTitle('Products-3');
        $document->setResourceSegment('/products/products-3');
        $document->setNavigationContexts(['main']);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(3, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);
        $this->assertEquals('/products/products-3', $main[2]['url']);

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1, false, 'main');
        $this->assertEquals(3, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);
        $this->assertEquals('/products/products-3', $main[2]['url']);
    }

    public function testNavigationStateTestParent()
    {
        $document = $this->data['products'];
        $document->setStructureType('simple');
        $document->setTitle('Products');
        $document->setResourceSegment('/products');
        $document->setParent($this->data['news/news-1']);
        $document->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($document, 'en');
        $this->documentManager->flush();

        $navigation = $this->navigation->getRootNavigation('sulu_io', 'en', 2);

        $this->assertCount(1, $navigation);
        $this->assertEquals('/news', $navigation[0]['url']);
        $this->assertCount(2, $navigation[0]['children']);
        $this->assertEquals('/news/news-1', $navigation[0]['children'][0]['url']);
        $this->assertEquals('/news/news-2', $navigation[0]['children'][1]['url']);
    }

    public function testNavigationOrder()
    {
        $main = $this->navigation->getNavigation($this->data['news']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(2, count($main));
        $this->assertEquals(0, count($main[0]['children']));
        $this->assertEquals(0, count($main[1]['children']));

        $this->assertEquals($this->data['news/news-1']->getUuid(), $main[0]['uuid']);
        $this->assertEquals('News-1', $main[0]['title']);
        $this->assertEquals('/news/news-1', $main[0]['url']);

        $this->assertEquals($this->data['news/news-2']->getUuid(), $main[1]['uuid']);
        $this->assertEquals('News-2', $main[1]['title']);
        $this->assertEquals('/news/news-2', $main[1]['url']);

        $session = $this->sessionManager->getSession();
        $session->getNodeByIdentifier($this->data['news/news-1']->getUuid())->setProperty('sulu:order', 100);
        $session->save();
        $session->refresh(false);

        $main = $this->navigation->getNavigation($this->data['news']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(2, count($main));
        $this->assertEquals(0, count($main[0]['children']));
        $this->assertEquals(0, count($main[1]['children']));

        $this->assertEquals($this->data['news/news-2']->getUuid(), $main[0]['uuid']);
        $this->assertEquals('News-2', $main[0]['title']);
        $this->assertEquals('/news/news-2', $main[0]['url']);

        $this->assertEquals($this->data['news/news-1']->getUuid(), $main[1]['uuid']);
        $this->assertEquals('News-1', $main[1]['title']);
        $this->assertEquals('/news/news-1', $main[1]['url']);
    }

    /**
     * @return PageDocument
     */
    private function createPageDocument()
    {
        return $this->documentManager->create('page');
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
