<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use PHPCR\NodeInterface;
use ReflectionMethod;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureExtension\StructureExtension;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Webspace;

class SitemapGeneratorTest extends PhpcrTestCase
{
    /**
     * @var StructureInterface[]
     */
    private $dataEn;

    /**
     * @var StructureInterface[]
     */
    private $dataEnUs;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var SitemapGeneratorInterface
     */
    private $sitemapGenerator;

    protected function setUp()
    {
        $this->prepareMapper();
        $this->dataEn = $this->prepareTestData();
        $this->dataEnUs = $this->prepareTestData('en_us');

        $this->structureManager->expects($this->any())
            ->method('getStructures')
            ->will($this->returnCallback(array($this, 'structuresCallback')));

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
            new SitemapContentQueryBuilder($this->structureManager, $this->languageNamespace)
        );
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager !== null) {
            return;
        }

        $this->webspace = new Webspace();
        $this->webspace->setKey('default');

        $local1 = new Localization();
        $local1->setLanguage('en');

        $local2 = new Localization();
        $local2->setLanguage('en');
        $local2->setCountry('us');

        $this->webspace->setLocalizations(array($local1, $local2));
        $this->webspace->setName('Default');

        $this->webspace->setNavigation(
            new Navigation(
                array(
                    new NavigationContext('main', array()),
                    new NavigationContext('footer', array()),
                )
            )
        );

        $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
        $this->webspaceManager
            ->expects($this->any())
            ->method('findWebspaceByKey')
            ->will($this->returnValue($this->webspace));
    }

    public function structureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        switch ($structureKey) {
            case 'default_template':
                return $this->getStructureMock($structureKey);
            case 'excerpt':
                return $this->getStructureMock($structureKey, false);
            case 'simple':
                return $this->getStructureMock($structureKey);
            case 'overview':
                return $this->getStructureMock($structureKey);
            case 'external-link':
                return $this->getStructureMock($structureKey, false);
            case 'internal-link':
                return $this->getStructureMock($structureKey, false);
            default:
                return;
        }
    }

    public function structuresCallback()
    {
        return array(
            $this->getStructureMock('default_template'),
            $this->getStructureMock('excerpt', 'name', false),
            $this->getStructureMock('simple', 'title'),
            $this->getStructureMock('overview'),
            $this->getStructureMock('external-link', 'test', false),
            $this->getStructureMock('internal-link', 'test', false),
        );
    }

    public function getExtensionCallback()
    {
        return new ExcerptStructureExtension($this->structureManager, $this->contentTypeManager);
    }

    public function getExtensionsCallback()
    {
        return array($this->getExtensionCallback());
    }

    /**
     * @param string $locale
     *
     * @return StructureInterface[]
     */
    private function prepareTestData($locale = 'en')
    {
        $data = array(
            'news' => array(
                'title' => 'News ' . $locale,
                'rl' => '/news',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('footer'),
            ),
            'products' => array(
                'title' => 'Products ' . $locale,
                'rl' => '/products',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('main'),
            ),
            'news/news-1' => array(
                'title' => 'News-1 ' . $locale,
                'rl' => '/news/news-1',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('main', 'footer'),
            ),
            'news/news-2' => array(
                'title' => 'News-2 ' . $locale,
                'rl' => '/news/news-2',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('main'),
            ),
            'products/products-1' => array(
                'title' => 'Products-1 ' . $locale,
                'external_url' => '123-123-123',
                'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
                'navContexts' => array('main', 'footer'),
            ),
            'products/products-2' => array(
                'title' => 'Products-2 ' . $locale,
                'external_url' => 'www.asdf.at',
                'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK,
                'navContexts' => array('main'),
            ),
            'products/products-3' => array(
                'title' => 'Products-3 ' . $locale,
                'external_url' => 'www.asdf.at',
                'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
                'navContexts' => array('main'),
            ),
        );

        $data['news'] = $this->mapper->save(
            $data['news'],
            'overview',
            'default',
            $locale,
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED
        );
        $data['news/news-1'] = $this->mapper->save(
            $data['news/news-1'],
            'simple',
            'default',
            $locale,
            1,
            true,
            null,
            $data['news']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );
        $data['news/news-2'] = $this->mapper->save(
            $data['news/news-2'],
            'simple',
            'default',
            $locale,
            1,
            true,
            null,
            $data['news']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );

        $data['products'] = $this->mapper->save(
            $data['products'],
            'overview',
            'default',
            $locale,
            1,
            true,
            null,
            null,
            StructureInterface::STATE_TEST
        );

        $data['products/products-1']['external_url'] = $data['products']->getUuid();
        $data['products/products-1'] = $this->mapper->save(
            $data['products/products-1'],
            'overview',
            'default',
            $locale,
            1,
            true,
            null,
            $data['products']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );
        $data['products/products-2'] = $this->mapper->save(
            $data['products/products-2'],
            'overview',
            'default',
            $locale,
            1,
            true,
            null,
            $data['products']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );
        $data['products/products-3']['external_url'] = $data['news']->getUuid();
        $data['products/products-3'] = $this->mapper->save(
            $data['products/products-3'],
            'overview',
            'default',
            $locale,
            1,
            true,
            null,
            $data['products']->getUuid(),
            StructureInterface::STATE_PUBLISHED
        );

        return $data;
    }

    private function getStructureMock($structureKey, $addResourceLocatorProperty = true)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure\Page',
            array($structureKey, 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $property = new Property('title', '', 'text_line', false, false, 1, 1, array());
        $property->setStructure($structureMock);
        $method->invokeArgs($structureMock, array($property));

        if ($addResourceLocatorProperty) {
            $property = new Property(
                'rl',
                '',
                'resource_locator',
                false,
                false,
                1,
                1,
                array(),
                array(new PropertyTag('sulu.rlp', 1))
            );
            $property->setStructure($structureMock);
            $method->invokeArgs($structureMock, array($property));
        } else {
            $property = new Property(
                'external_url',
                '',
                'text_line',
                false,
                false,
                1,
                1,
                array(),
                array(new PropertyTag('sulu.rlp', 1))
            );
            $property->setStructure($structureMock);
            $method->invokeArgs($structureMock, array($property));
        }

        return $structureMock;
    }

    public function testGenerateAllFlat()
    {
        $result = $this->sitemapGenerator->generateAllLocals('default', true);

        $this->assertEquals(11, sizeof($result));
        $this->assertEquals('', $result[0]['title']);
        $this->assertEquals('News en', $result[1]['title']);
        $this->assertEquals('News-1 en', $result[2]['title']);
        $this->assertEquals('News-2 en', $result[3]['title']);
        $this->assertEquals('Products-2 en', $result[4]['title']);
        $this->assertEquals('Products-3 en', $result[5]['title']);
        $this->assertEquals('News en_us', $result[6]['title']);
        $this->assertEquals('News-1 en_us', $result[7]['title']);
        $this->assertEquals('News-2 en_us', $result[8]['title']);
        // Products-1 en/en_us is a internal link to the unpublished page products (not in result)
        $this->assertEquals('Products-2 en_us', $result[9]['title']);
        $this->assertEquals('Products-3 en_us', $result[10]['title']);

        $this->assertEquals('/', $result[0]['url']);
        $this->assertEquals('/news', $result[1]['url']);
        $this->assertEquals('/news/news-1', $result[2]['url']);
        $this->assertEquals('/news/news-2', $result[3]['url']);
        $this->assertEquals('http://www.asdf.at', $result[4]['url']);
        $this->assertEquals('/news', $result[5]['url']);
        $this->assertEquals('/news', $result[6]['url']);
        $this->assertEquals('/news/news-1', $result[7]['url']);
        $this->assertEquals('/news/news-2', $result[8]['url']);
        $this->assertEquals('http://www.asdf.at', $result[9]['url']);
        $this->assertEquals('/news', $result[10]['url']);

        $this->assertEquals(1, $result[0]['nodeType']);
        $this->assertEquals(1, $result[1]['nodeType']);
        $this->assertEquals(1, $result[2]['nodeType']);
        $this->assertEquals(1, $result[3]['nodeType']);
        $this->assertEquals(4, $result[4]['nodeType']);
        $this->assertEquals(2, $result[5]['nodeType']);
        $this->assertEquals(1, $result[6]['nodeType']);
        $this->assertEquals(1, $result[7]['nodeType']);
        $this->assertEquals(1, $result[8]['nodeType']);
        $this->assertEquals(4, $result[9]['nodeType']);
        $this->assertEquals(2, $result[10]['nodeType']);
    }

    public function testGenerateFlat()
    {
        $result = $this->sitemapGenerator->generate('default', 'en', true);

        $this->assertEquals(6, sizeof($result));
        $this->assertEquals('', $result[0]['title']);
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
        $result = $this->sitemapGenerator->generate('default', 'en');

        $root = $result;
        $this->assertEquals('', $root['title']);
        $this->assertEquals('/', $root['url']);
        $this->assertEquals(1, $root['nodeType']);

        $layer1 = array_values($root['children']);

        $this->assertEquals(3, sizeof($layer1));

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

class ExcerptStructureExtension extends StructureExtension
{
    /**
     * name of structure extension.
     */
    const EXCERPT_EXTENSION_NAME = 'excerpt';

    /**
     * will be filled with data in constructor
     * {@inheritdoc}
     */
    protected $properties = array();

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
        $data = array();
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
