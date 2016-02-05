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
use PHPCR\SessionInterface;
use ReflectionMethod;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyTag;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Navigation;

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
     * @var SessionInterface
     */
    private $session;

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

    protected function setUp()
    {
        $this->initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->extensionManager = $this->getContainer()->get('sulu_content.extension.manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->languageNamespace = 'i18n';
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
        $data = [
            'news' => [
                'title' => 'News',
                'url' => '/news',
                'ext' => ['excerpt' => ['title' => 'Excerpt News']],
                'navContexts' => ['footer'],
            ],
            'products' => [
                'title' => 'Products',
                'url' => '/products',
                'ext' => ['excerpt' => ['title' => 'Excerpt Products']],
                'navContexts' => ['main'],
            ],
            'news/news-1' => [
                'title' => 'News-1',
                'url' => '/news/news-1',
                'ext' => ['excerpt' => ['title' => 'Excerpt News 1']],
                'navContexts' => ['main', 'footer'],
            ],
            'news/news-2' => [
                'title' => 'News-2',
                'url' => '/news/news-2',
                'ext' => ['excerpt' => ['title' => 'Excerpt News 2']],
                'navContexts' => ['main'],
            ],
            'products/products-1' => [
                'title' => 'Products-1',
                'url' => '/products/products-1',
                'ext' => ['excerpt' => ['title' => 'Excerpt Products 1']],
                'navContexts' => ['main', 'footer'],
            ],
            'products/products-2' => [
                'title' => 'Products-2',
                'url' => '/products/products-2',
                'ext' => ['excerpt' => ['title' => 'Excerpt Products 2']],
                'navContexts' => ['main'],
            ],
        ];

        $data['news'] = $this->mapper->save(
            $data['news'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED
        );
        $data['news/news-1'] = $this->mapper->save(
            $data['news/news-1'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $data['news']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );
        $data['news/news-2'] = $this->mapper->save(
            $data['news/news-2'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $data['news']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );

        $data['products'] = $this->mapper->save(
            $data['products'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED
        );
        $data['products/products-1'] = $this->mapper->save(
            $data['products/products-1'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $data['products']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );
        $data['products/products-2'] = $this->mapper->save(
            $data['products/products-2'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $data['products']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );

        return $data;
    }

    private function getStructureMock($name, $rlp = true)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Compat\Structure\Page',
            [$name, 'asdf', 'asdf', 2400]
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $property = new Property('title', '', 'text_line', true, true, 1, 1, []);
        $property->setStructure($structureMock);
        $method->invokeArgs($structureMock, [$property]);

        if ($rlp) {
            $property = new Property(
                'url',
                '',
                'resource_locator',
                true,
                true,
                1,
                1,
                [],
                [new PropertyTag('sulu.rlp', 1)]
            );
            $property->setStructure($structureMock);
            $method->invokeArgs($structureMock, [$property]);
        }

        return $structureMock;
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

        $data['news'] = $this->mapper->save(
            [
                'title' => 'SubNews',
                'url' => '/asdf',
                'navContexts' => ['footer'],
            ],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $this->data['news/news-1']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );

        $result = $this->navigation->getNavigation($this->data['news']->getUuid(), 'sulu_io', 'en', 2, true);
        $this->assertEquals(3, count($result));
        $this->assertEquals('News-1', $result[0]['title']);
        $this->assertEquals('News-2', $result[1]['title']);
        $this->assertEquals('SubNews', $result[2]['title']);
    }

    public function testNavigationExcerpt()
    {
        $data['news'] = $this->mapper->save(
            [
                'title' => 'SubNews',
                'url' => '/asdf',
                'navContexts' => ['footer'],
            ],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $this->data['news/news-1']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );

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
        $this->assertEquals('News-1', $result[0]['title']);
        $this->assertEquals('Excerpt News 1', $result[0]['excerpt']['title']);

        $this->markTestSkipped('This method does not work at more than one level. See issue #1252');

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
        $data = [
            'title' => 'Products-3',
            'url' => '/products/products-3',
        ];

        $this->data['products/products-3'] = $this->mapper->save(
            $data,
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $this->data['products']->getUuid(),
            StructureInterface::STATE_TEST
        );

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(2, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);

        $this->data['products/products-3'] = $this->mapper->save(
            $data,
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            $this->data['products/products-3']->getUuid(),
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1);
        $this->assertEquals(3, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);
        $this->assertEquals('/products/products-3', $main[2]['url']);

        $main = $this->navigation->getNavigation($this->data['products']->getUuid(), 'sulu_io', 'en', 1, false, 'main');
        $this->assertEquals(2, count($main));
        $this->assertEquals('/products/products-1', $main[0]['url']);
        $this->assertEquals('/products/products-2', $main[1]['url']);

        $data = [
            'title' => 'Products-3',
            'url' => '/products/products-3',
            'navContexts' => ['main'],
        ];
        $this->data['products/products-3'] = $this->mapper->save(
            $data,
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            $this->data['products/products-3']->getUuid()
        );

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
        $this->data['products'] = $this->mapper->save(
            ['title' => 'Products', 'url' => '/products'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            $this->data['products']->getUuid(),
            null,
            StructureInterface::STATE_TEST
        );

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
