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

use ReflectionMethod;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Webspace;

class SitemapGeneratorTest extends PhpcrTestCase
{
    /**
     * @var StructureInterface[]
     */
    private $dataEN;

    /**
     * @var StructureInterface[]
     */
    private $dataENUS;

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
        $this->dataEN = $this->prepareTestData();
        $this->dataENUS = $this->prepareTestData('en_us');

        $this->structureManager->expects($this->any())
            ->method('getStructures')
            ->will($this->returnCallback(array($this, 'structuresCallback')));

        $this->contents->setProperty('i18n:en-state', Structure::STATE_PUBLISHED);
        $this->contents->setProperty('i18n:en-nodeType', Structure::NODE_TYPE_CONTENT);
        $this->session->save();

        $this->sitemapGenerator = new SitemapGenerator(
            $this->sessionManager,
            $this->structureManager,
            $this->webspaceManager,
            $this->languageNamespace
        );
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $this->webspace = new Webspace();
            $this->webspace->setKey('default');

            $local1 = new Localization();
            $local1->setLanguage('en');

            $local2 = new Localization();
            $local2->setLanguage('en');
            $local2->setCountry('us');

            $this->webspace->setLocalizations(array($local1, $local2));
            $this->webspace->setName('Default');

            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager
                ->expects($this->any())
                ->method('findWebspaceByKey')
                ->will($this->returnValue($this->webspace));
        }
    }

    public function structureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey == 'default_template') {
            return $this->getStructureMock($structureKey);
        } elseif ($structureKey == 'simple') {
            return $this->getStructureMock($structureKey, 'title');
        } elseif ($structureKey == 'overview') {
            return $this->getStructureMock($structureKey);
        } elseif ($structureKey == 'external-link') {
            return $this->getStructureMock($structureKey, 'test', false);
        } elseif ($structureKey == 'internal-link') {
            return $this->getStructureMock($structureKey, 'test', false);
        }

        return null;
    }

    public function structuresCallback()
    {
        return array(
            $this->getStructureMock('default_template'),
            $this->getStructureMock('simple', 'title'),
            $this->getStructureMock('overview'),
            $this->getStructureMock('external-link', 'test', false),
            $this->getStructureMock('internal-link', 'test', false)
        );
    }

    /**
     * @param string $locale
     * @return StructureInterface[]
     */
    private function prepareTestData($locale = 'en')
    {
        $data = array(
            'news' => array(
                'name' => 'News ' . $locale,
                'rl' => '/news',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('footer')
            ),
            'products' => array(
                'name' => 'Products ' . $locale,
                'rl' => '/products',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('main')
            ),
            'news/news-1' => array(
                'title' => 'News-1 ' . $locale,
                'rl' => '/news/news-1',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('main', 'footer')
            ),
            'news/news-2' => array(
                'title' => 'News-2 ' . $locale,
                'rl' => '/news/news-2',
                'nodeType' => Structure::NODE_TYPE_CONTENT,
                'navContexts' => array('main')
            ),
            'products/products-1' => array(
                'test' => 'Products-1 ' . $locale,
                'external_url' => '123-123-123',
                'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
                'navContexts' => array('main', 'footer')
            ),
            'products/products-2' => array(
                'test' => 'Products-2 ' . $locale,
                'external_url' => 'www.asdf.at',
                'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK,
                'navContexts' => array('main')
            )
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

        return $data;
    }

    private function getStructureMock($structureKey, $nodeName = 'name', $rlp = true)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array($structureKey, 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    $nodeName,
                    '',
                    'text_line',
                    false,
                    false,
                    1,
                    1,
                    array(),
                    array(new PropertyTag('sulu.node.name', 1))
                )
            )
        );

        if ($rlp) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'rl',
                        '',
                        'resource_locator',
                        false,
                        false,
                        1,
                        1,
                        array(),
                        array(new PropertyTag('sulu.rlp', 1))
                    )
                )
            );
        } else {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'external_url',
                        '',
                        'text_line',
                        false,
                        false,
                        1,
                        1,
                        array(),
                        array(new PropertyTag('sulu.rlp', 1))
                    )
                )
            );
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
        $this->assertEquals('Products-1 en', $result[4]['title']);
        $this->assertEquals('Products-2 en', $result[5]['title']);
        $this->assertEquals('News en_us', $result[6]['title']);
        $this->assertEquals('News-1 en_us', $result[7]['title']);
        $this->assertEquals('News-2 en_us', $result[8]['title']);
        $this->assertEquals('Products-1 en_us', $result[9]['title']);
        $this->assertEquals('Products-2 en_us', $result[10]['title']);

        $this->assertEquals('/', $result[0]['url']);
        $this->assertEquals('/news', $result[1]['url']);
        $this->assertEquals('/news/news-1', $result[2]['url']);
        $this->assertEquals('/news/news-2', $result[3]['url']);
        $this->assertEquals($this->dataEN['products']->getUuid(), $result[4]['url']);
        $this->assertEquals('www.asdf.at', $result[5]['url']);
        $this->assertEquals('/news', $result[6]['url']);
        $this->assertEquals('/news/news-1', $result[7]['url']);
        $this->assertEquals('/news/news-2', $result[8]['url']);
        $this->assertEquals($this->dataENUS['products']->getUuid(), $result[9]['url']);
        $this->assertEquals('www.asdf.at', $result[10]['url']);

        $this->assertEquals(1, $result[0]['nodeType']);
        $this->assertEquals(1, $result[1]['nodeType']);
        $this->assertEquals(1, $result[2]['nodeType']);
        $this->assertEquals(1, $result[3]['nodeType']);
        $this->assertEquals(2, $result[4]['nodeType']);
        $this->assertEquals(4, $result[5]['nodeType']);
        $this->assertEquals(1, $result[6]['nodeType']);
        $this->assertEquals(1, $result[7]['nodeType']);
        $this->assertEquals(1, $result[8]['nodeType']);
        $this->assertEquals(2, $result[9]['nodeType']);
        $this->assertEquals(4, $result[10]['nodeType']);
    }

    public function testGenerateFlat()
    {
        $result = $this->sitemapGenerator->generate('default', 'en', true);

        $this->assertEquals(6, sizeof($result));
        $this->assertEquals('', $result[0]['title']);
        $this->assertEquals('News en', $result[1]['title']);
        $this->assertEquals('News-1 en', $result[2]['title']);
        $this->assertEquals('News-2 en', $result[3]['title']);
        $this->assertEquals('Products-1 en', $result[4]['title']);
        $this->assertEquals('Products-2 en', $result[5]['title']);

        $this->assertEquals('/', $result[0]['url']);
        $this->assertEquals('/news', $result[1]['url']);
        $this->assertEquals('/news/news-1', $result[2]['url']);
        $this->assertEquals('/news/news-2', $result[3]['url']);
        $this->assertEquals($this->dataEN['products']->getUuid(), $result[4]['url']);
        $this->assertEquals('www.asdf.at', $result[5]['url']);

        $this->assertEquals(1, $result[0]['nodeType']);
        $this->assertEquals(1, $result[1]['nodeType']);
        $this->assertEquals(1, $result[2]['nodeType']);
        $this->assertEquals(1, $result[3]['nodeType']);
        $this->assertEquals(2, $result[4]['nodeType']);
        $this->assertEquals(4, $result[5]['nodeType']);
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

        $this->assertEquals('Products-1 en', $layer1[1]['title']);
        $this->assertEquals(2, $layer1[1]['nodeType']);
        $this->assertEquals($this->dataEN['products']->getUuid(), $layer1[1]['url']);

        $this->assertEquals('Products-2 en', $layer1[2]['title']);
        $this->assertEquals('www.asdf.at', $layer1[2]['url']);
        $this->assertEquals(4, $layer1[2]['nodeType']);

        $layer21 = array_values($layer1[0]['children']);

        $this->assertEquals('News-1 en', $layer21[0]['title']);
        $this->assertEquals('/news/news-1', $layer21[0]['url']);
        $this->assertEquals(1, $layer21[0]['nodeType']);

        $this->assertEquals('News-2 en', $layer21[1]['title']);
        $this->assertEquals('/news/news-2', $layer21[1]['url']);
        $this->assertEquals(1, $layer21[1]['nodeType']);
    }
}
