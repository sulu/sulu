<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Repository;

use PHPCR\NodeInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Sulu\Bundle\AdminBundle\UserManager\CurrentUserDataInterface;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Types\SmartContent\SmartContentQueryBuilder;
use Sulu\Bundle\ContentBundle\Repository\NodeRepository;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyTag;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\StructureExtension\StructureExtension;
use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Webspace;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Content\Extension\ExtensionInterface;

/**
 * @group functional
 * @group repository
 */
class NodeRepositoryTest extends SuluTestCase
{
    /**
     * @var NodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var ExtensionInterface[]
     */
    private $extensions;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->extensions = array(new TestExtension('test1', 'test1'));
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->nodeRepository = $this->getContainer()->get('sulu_content.node_repository');
    }

    private function prepareGetTestData()
    {
        $data = array(
            'title' => 'Testtitle',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'Test'
        );

        return $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1, true, null, null, Structure::STATE_PUBLISHED);
    }

    public function testGet()
    {
        $structure = $this->prepareGetTestData();

        $result = $this->nodeRepository->getNode($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals($structure->getProperty('title')->getValue(), $result['title']);
        $this->assertEquals($structure->getProperty('url')->getValue(), $result['url']);
    }

    public function testDelete()
    {
        $structure = $this->prepareGetTestData();

        $this->nodeRepository->deleteNode($structure->getUuid(), 'sulu_io');

        $this->setExpectedException(DocumentNotFoundException::class);
        $this->nodeRepository->getNode($structure->getUuid(), 'sulu_io', 'en');
    }

    public function testSave()
    {
        $structure = $this->prepareGetTestData();

        $this->nodeRepository->saveNode(
            array(
                'title' => 'asdf',
                'url' => '/foo',
            ),
            'overview',
            'sulu_io',
            'en',
            1,
            $structure->getUuid()
        );

        $result = $this->nodeRepository->getNode($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals('asdf', $result['title']);
        $this->assertEquals($structure->getProperty('url')->getValue(), $result['url']);
    }

    public function testSaveNewNode()
    {
        $structure = $this->prepareGetTestData();

        $node = $this->nodeRepository->saveNode(
            array(
                'title' => 'asdf',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test/asdf',
                'article' => 'Test'
            ),
            'overview',
            'sulu_io',
            'en',
            1,
            null,
            $structure->getUuid()
        );

        $result = $this->nodeRepository->getNode($node['id'], 'sulu_io', 'en');

        $this->assertEquals('asdf', $result['title']);
        $this->assertEquals('/news/test/asdf', $result['url']);

        $node = $this->nodeRepository->saveNode(
            array(
                'title' => 'asdf',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/asdf',
                'article' => 'Test'
            ),
            'overview',
            'sulu_io',
            'en',
            1
        );

        $result = $this->nodeRepository->getNode($node['id'], 'sulu_io', 'en');

        $this->assertEquals('asdf', $result['title']);
        $this->assertEquals('/asdf', $result['url']);
    }

    public function testIndexNode()
    {
        $data = array(
            'title' => 'Testtitle',
            'url' => '/',
        );
        $this->nodeRepository->saveIndexNode(
            $data,
            'overview',
            'sulu_io',
            'en',
            1
        );

        $index = $this->nodeRepository->getIndexNode('sulu_io', 'en');

        $this->assertEquals('Testtitle', $index['title']);
    }

    public function testGetWebspaceNode()
    {
        $result = $this->nodeRepository->getWebspaceNode('sulu_io', 'en');

        $this->assertEquals('Sulu CMF', $result['_embedded']['nodes'][0]['title']);
    }

    public function testGetWebspaceNodes()
    {
        $result = $this->nodeRepository->getWebspaceNodes('en');

        $this->assertEquals('Sulu CMF', $result['_embedded']['nodes'][0]['title']);
        // TODO add more webspaces when changed to SuluTestCase
    }

    public function testGetNodesTree()
    {
        $data = $this->prepareGetTestData();

        // without webspace
        $result = $this->nodeRepository->getNodesTree($data->getUuid(), 'sulu_io', 'en', false, false);
        $this->assertEquals(1, sizeof($result['_embedded']['nodes']));
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
        $this->assertFalse($result['_embedded']['nodes'][0]['hasSub']);

        // with webspace
        $result = $this->nodeRepository->getNodesTree($data->getUuid(), 'sulu_io', 'en', false, true);
        $this->assertEquals(1, sizeof($result['_embedded']['nodes']));
        $this->assertEquals('Sulu CMF', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/', $result['_embedded']['nodes'][0]['path']);
        $this->assertTrue($result['_embedded']['nodes'][0]['hasSub']);

        $this->assertEquals(1, sizeof($result['_embedded']['nodes'][0]['_embedded']));
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['_embedded']['nodes'][0]['path']);
        $this->assertFalse($result['_embedded']['nodes'][0]['_embedded']['nodes'][0]['hasSub']);
    }

    public function testGetNodesTreeWithGhosts()
    {
        $data = $this->prepareGetTestData();

        $result = $this->nodeRepository->getNodesTree($data->getUuid(), 'sulu_io', 'de', false, false);
        $this->assertEquals(1, sizeof($result['_embedded']['nodes']));
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
        $this->assertEquals('ghost', $result['_embedded']['nodes'][0]['type']['name']);
        $this->assertEquals('en', $result['_embedded']['nodes'][0]['type']['value']);
        $this->assertFalse($result['_embedded']['nodes'][0]['hasSub']);
    }

    public function testExtensionData()
    {
        $data = $this->prepareGetTestData();
        $extData = array('a' => 'A', 'b' => 'B');

        $result = $this->nodeRepository->loadExtensionData($data->getUuid(), 'test1', 'sulu_io', 'en');
        $this->assertEquals('', $result['a']);
        $this->assertEquals('', $result['b']);
        $this->assertEquals('/testtitle', $result['path']);

        $result = $this->nodeRepository->saveExtensionData($data->getUuid(), $extData, 'test1', 'sulu_io', 'en', 1);
        $this->assertEquals('A', $result['a']);
        $this->assertEquals('B', $result['b']);
        $this->assertEquals('/testtitle', $result['path']);

        $result = $this->nodeRepository->loadExtensionData($data->getUuid(), 'test1', 'sulu_io', 'en');
        $this->assertEquals('A', $result['a']);
        $this->assertEquals('B', $result['b']);
        $this->assertEquals('/testtitle', $result['path']);
    }

    public function testGetByIds()
    {
        $data = $this->prepareGetTestData();

        $result = $this->nodeRepository->getNodesByIds(array(), 'sulu_io', 'en');
        $this->assertEquals(0, sizeof($result['_embedded']['nodes']));
        $this->assertEquals(0, $result['total']);

        $result = $this->nodeRepository->getNodesByIds(
            array(
                $data->getUuid()
            ),
            'sulu_io',
            'en'
        );
        $this->assertEquals(1, sizeof($result['_embedded']['nodes']));
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
    }

    public function testGetByIdsNotExisitingID()
    {
        $data = $this->prepareGetTestData();

        $result = $this->nodeRepository->getNodesByIds(array(), 'sulu_io', 'en');
        $this->assertEquals(0, sizeof($result['_embedded']['nodes']));
        $this->assertEquals(0, $result['total']);

        $result = $this->nodeRepository->getNodesByIds(
            array(
                $data->getUuid(),
                '556ce63c-97a3-4a03-81a9-719bc01234e6'
            ),
            'sulu_io',
            'en'
        );
        $this->assertEquals(1, sizeof($result['_embedded']['nodes']));
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
    }

    public function testGetFilteredNodesInOrder()
    {
        $data = array(
            array(
                'title' => 'Testtitle1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test1',
                'article' => 'Test'
            ),
            array(
                'title' => 'Testtitle2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test2',
                'article' => 'Test'
            ),
        );

        foreach ($data as &$element) {
            $element = $this->mapper->save(
                $element,
                'overview',
                'sulu_io',
                'en',
                1,
                true,
                null,
                null,
                StructureInterface::STATE_PUBLISHED
            );
            sleep(1);
        }

        $nodes = $this->nodeRepository->getFilteredNodes(
            array('sortBy' => array('published'), 'sortMethod' => 'asc'),
            'en',
            'sulu_io'
        );

        $this->assertEquals('Testtitle1', $nodes[0]['title']);
        $this->assertEquals('Testtitle2', $nodes[1]['title']);

        $nodes = $this->nodeRepository->getFilteredNodes(
            array('sortBy' => array('published'), 'sortMethod' => 'desc'),
            'en',
            'sulu_io'
        );

        $this->assertEquals('Testtitle2', $nodes[0]['title']);
        $this->assertEquals('Testtitle1', $nodes[1]['title']);
    }

    public function testGetFilteredNodesInOrderByTitle()
    {
        $data = array(
            array(
                'title' => 'hello you',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test1',
                'article' => 'Test'
            ),
            array(
                'title' => 'Hello me',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test2',
                'article' => 'Test'
            ),
            array(
                'title' => 'Test',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test3',
                'article' => 'Test'
            ),
        );

        foreach ($data as &$element) {
            $element = $this->mapper->save(
                $element,
                'overview',
                'sulu_io',
                'en',
                1,
                true,
                null,
                null,
                StructureInterface::STATE_PUBLISHED
            );
            sleep(1);
        }

        $nodes = $this->nodeRepository->getFilteredNodes(
            array('sortBy' => array('title'), 'sortMethod' => 'asc'),
            'en',
            'sulu_io'
        );

        $this->assertEquals('Hello me', $nodes[0]['title']);
        $this->assertEquals('hello you', $nodes[1]['title']);
        $this->assertEquals('Test', $nodes[2]['title']);

        $nodes = $this->nodeRepository->getFilteredNodes(
            array('sortBy' => array('title'), 'sortMethod' => 'desc'),
            'en',
            'sulu_io'
        );

        $this->assertEquals('Hello me', $nodes[2]['title']);
        $this->assertEquals('hello you', $nodes[1]['title']);
        $this->assertEquals('Test', $nodes[0]['title']);
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareTestDataMoveCopy()
    {
        $data = array(
            array(
                'title' => 'Testtitle1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test1',
                'article' => 'Test'
            ),
            array(
                'title' => 'Testtitle2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test2',
                'article' => 'Test'
            ),
        );

        foreach ($data as &$element) {
            $element = $this->mapper->save(
                $element,
                'overview',
                'sulu_io',
                'en',
                1,
                true,
                null,
                null,
                StructureInterface::STATE_PUBLISHED
            );
        }

        return $data;
    }

    public function testMove()
    {
        $data = $this->prepareTestDataMoveCopy();

        $rootNode = $this->nodeRepository->getIndexNode('sulu_io', 'en');

        $result = $this->nodeRepository->moveNode($data[0]->getUuid(), $data[1]->getUuid(), 'sulu_io', 'en', 2);
        $structure = $this->nodeRepository->getNode($data[0]->getUuid(), 'sulu_io', 'en');

        // check result
        $this->assertEquals($structure, $result);

        // check some properties
        $this->assertEquals($data[0]->getUuid(), $result['id']);
        $this->assertEquals('Testtitle1', $result['title']);
        $this->assertEquals('/testtitle2/testtitle1', $result['path']);
        $this->assertEquals('/news/test2/test1', $result['url']);
        $this->assertEquals(2, $result['changer']);

        // check none existing source node
        $firstLayerNodes = $this->nodeRepository->getNodes($rootNode['id'], 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($firstLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle2', $firstLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2', $firstLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('/news/test2', $firstLayerNodes['_embedded']['nodes'][0]['url']);

        $secondLayerNodes = $this->nodeRepository->getNodes($data[1]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($secondLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2/testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('/news/test2/test1', $secondLayerNodes['_embedded']['nodes'][0]['url']);
    }

    public function testMoveNonExistingSource()
    {
        $data = $this->prepareTestDataMoveCopy();
        $this->setExpectedException('Sulu\Component\Rest\Exception\RestException');

        $this->nodeRepository->moveNode('123-123', $data[1]->getUuid(), 'sulu_io', 'en', 2);
    }

    public function testMoveNonExistingDestination()
    {
        $data = $this->prepareTestDataMoveCopy();
        $this->setExpectedException('Sulu\Component\Rest\Exception\RestException');

        $this->nodeRepository->moveNode($data[0]->getUuid(), '123-123', 'sulu_io', 'en', 2);
    }

    public function testMoveInternalLink()
    {
        $data = $this->prepareTestDataMoveCopy();

        $newData = array(
            'title' => 'Testtitle1',
            'internal_link' => $data[1]->getUuid(),
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK
        );

        $data[0] = $this->mapper->save(
            $newData,
            'internal-link',
            'sulu_io',
            'en',
            1,
            true,
            $data[0]->getUuid(),
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $rootNode = $this->nodeRepository->getIndexNode('sulu_io', 'en');

        $result = $this->nodeRepository->moveNode($data[0]->getUuid(), $data[1]->getUuid(), 'sulu_io', 'en', 2);
        $structure = $this->nodeRepository->getNode($data[0]->getUuid(), 'sulu_io', 'en');

        // check result
        $this->assertEquals($structure, $result);

        // check some properties
        $this->assertEquals($data[0]->getUuid(), $result['id']);
        $this->assertEquals('Testtitle1', $result['title']);
        $this->assertEquals('/testtitle2/testtitle1', $result['path']);
        $this->assertEquals($data[1]->getUuid(), $result['internal_link']);
        $this->assertEquals(2, $result['changer']);

        // check none existing source node
        $firstLayerNodes = $this->nodeRepository->getNodes($rootNode['id'], 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($firstLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle2', $firstLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2', $firstLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('/news/test2', $firstLayerNodes['_embedded']['nodes'][0]['url']);

        $secondLayerNodes = $this->nodeRepository->getNodes($data[1]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($secondLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2/testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals($data[1]->getUuid(), $secondLayerNodes['_embedded']['nodes'][0]['internal_link']);
    }

    public function testMoveExternalLink()
    {
        $data = $this->prepareTestDataMoveCopy();

        $newData = array(
            'title' => 'Testtitle1',
            'external_link' => 'www.google.at',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK
        );

        $data[0] = $this->mapper->save(
            $newData,
            'external-link',
            'sulu_io',
            'en',
            1,
            true,
            $data[0]->getUuid(),
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $rootNode = $this->nodeRepository->getIndexNode('sulu_io', 'en');

        $result = $this->nodeRepository->moveNode($data[0]->getUuid(), $data[1]->getUuid(), 'sulu_io', 'en', 2);
        $structure = $this->nodeRepository->getNode($data[0]->getUuid(), 'sulu_io', 'en');

        // check result
        $this->assertEquals($structure, $result);

        // check some properties
        $this->assertEquals($data[0]->getUuid(), $result['id']);
        $this->assertEquals('Testtitle1', $result['title']);
        $this->assertEquals('/testtitle2/testtitle1', $result['path']);
        $this->assertEquals('www.google.at', $result['external_link']);
        $this->assertEquals(2, $result['changer']);

        // check none existing source node
        $firstLayerNodes = $this->nodeRepository->getNodes($rootNode['id'], 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($firstLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle2', $firstLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2', $firstLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('/news/test2', $firstLayerNodes['_embedded']['nodes'][0]['url']);

        $secondLayerNodes = $this->nodeRepository->getNodes($data[1]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($secondLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2/testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('www.google.at', $secondLayerNodes['_embedded']['nodes'][0]['external_link']);
    }

    public function testCopy()
    {
        $data = $this->prepareTestDataMoveCopy();

        $rootNode = $this->nodeRepository->getIndexNode('sulu_io', 'en');

        $result = $this->nodeRepository->copyNode($data[0]->getUuid(), $data[1]->getUuid(), 'sulu_io', 'en', 2);
        $structure = $this->nodeRepository->getNode($data[0]->getUuid(), 'sulu_io', 'en');

        // check result
        $this->assertNotEquals($structure, $result);

        // check some properties
        $this->assertNotEquals($data[0]->getUuid(), $result['id']);
        $this->assertEquals('Testtitle1', $result['title']);
        $this->assertEquals('/testtitle2/testtitle1', $result['path']);
        $this->assertEquals(2, $result['changer']);

        // check none existing source node
        $firstLayerNodes = $this->nodeRepository->getNodes($rootNode['id'], 'sulu_io', 'en');
        $this->assertEquals(2, sizeof($firstLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $firstLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle1', $firstLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('Testtitle2', $firstLayerNodes['_embedded']['nodes'][1]['title']);
        $this->assertEquals('/testtitle2', $firstLayerNodes['_embedded']['nodes'][1]['path']);

        $secondLayerNodes = $this->nodeRepository->getNodes($data[1]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($secondLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2/testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['path']);
    }

    public function testCopyNonExistingSource()
    {
        $data = $this->prepareTestDataMoveCopy();
        $this->setExpectedException('Sulu\Component\Rest\Exception\RestException');

        $this->nodeRepository->copyNode('123-123', $data[1]->getUuid(), 'sulu_io', 'en', 2);
    }

    public function testCopyNonExistingDestination()
    {
        $data = $this->prepareTestDataMoveCopy();
        $this->setExpectedException('Sulu\Component\Rest\Exception\RestException');

        $this->nodeRepository->copyNode($data[0]->getUuid(), '123-123', 'sulu_io', 'en', 2);
    }

    public function testCopyInternalLink()
    {
        $data = $this->prepareTestDataMoveCopy();

        $newData = array(
            'title' => 'Testtitle1',
            'internal_link' => $data[1]->getUuid(),
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK
        );

        $data[0] = $this->mapper->save(
            $newData,
            'internal-link',
            'sulu_io',
            'en',
            1,
            true,
            $data[0]->getUuid(),
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $rootNode = $this->nodeRepository->getIndexNode('sulu_io', 'en');

        $result = $this->nodeRepository->copyNode($data[0]->getUuid(), $data[1]->getUuid(), 'sulu_io', 'en', 2);
        $structure = $this->nodeRepository->getNode($data[0]->getUuid(), 'sulu_io', 'en');

        // check result
        $this->assertNotEquals($structure, $result);

        // check some properties
        $this->assertNotEquals($data[0]->getUuid(), $result['id']);
        $this->assertEquals('Testtitle1', $result['title']);
        $this->assertEquals('/testtitle2/testtitle1', $result['path']);
        $this->assertEquals(2, $result['changer']);

        // check none existing source node
        $firstLayerNodes = $this->nodeRepository->getNodes($rootNode['id'], 'sulu_io', 'en');
        $this->assertEquals(2, sizeof($firstLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $firstLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle1', $firstLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals($data[1]->getUuid(), $firstLayerNodes['_embedded']['nodes'][0]['internal_link']);
        $this->assertEquals('Testtitle2', $firstLayerNodes['_embedded']['nodes'][1]['title']);
        $this->assertEquals('/testtitle2', $firstLayerNodes['_embedded']['nodes'][1]['path']);

        $secondLayerNodes = $this->nodeRepository->getNodes($data[1]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($secondLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2/testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals($data[1]->getUuid(), $secondLayerNodes['_embedded']['nodes'][0]['internal_link']);
    }

    public function testCopyExternalLink()
    {
        $data = $this->prepareTestDataMoveCopy();

        $newData = array(
            'title' => 'Testtitle1',
            'external_link' => 'www.google.at',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK
        );

        $data[0] = $this->mapper->save(
            $newData,
            'external-link',
            'sulu_io',
            'en',
            1,
            true,
            $data[0]->getUuid(),
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $rootNode = $this->nodeRepository->getIndexNode('sulu_io', 'en');

        $result = $this->nodeRepository->copyNode($data[0]->getUuid(), $data[1]->getUuid(), 'sulu_io', 'en', 2);
        $structure = $this->nodeRepository->getNode($data[0]->getUuid(), 'sulu_io', 'en');

        // check result
        $this->assertNotEquals($structure, $result);

        // check some properties
        $this->assertNotEquals($data[0]->getUuid(), $result['id']);
        $this->assertEquals('Testtitle1', $result['title']);
        $this->assertEquals('/testtitle2/testtitle1', $result['path']);
        $this->assertEquals(2, $result['changer']);

        // check none existing source node
        $firstLayerNodes = $this->nodeRepository->getNodes($rootNode['id'], 'sulu_io', 'en');
        $this->assertEquals(2, sizeof($firstLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $firstLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle1', $firstLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('www.google.at', $firstLayerNodes['_embedded']['nodes'][0]['external_link']);
        $this->assertEquals('Testtitle2', $firstLayerNodes['_embedded']['nodes'][1]['title']);
        $this->assertEquals('/testtitle2', $firstLayerNodes['_embedded']['nodes'][1]['path']);

        $secondLayerNodes = $this->nodeRepository->getNodes($data[1]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals(1, sizeof($secondLayerNodes['_embedded']['nodes']));
        $this->assertEquals('Testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle2/testtitle1', $secondLayerNodes['_embedded']['nodes'][0]['path']);
        $this->assertEquals('www.google.at', $secondLayerNodes['_embedded']['nodes'][0]['external_link']);
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareOrderBeforeData()
    {
        $data = array(
            array(
                'title' => 'Test1',
                'url' => '/news/test1'
            ),
            array(
                'title' => 'Test2',
                'url' => '/news/test2'
            ),
            array(
                'title' => 'Test3',
                'url' => '/news/test3'
            ),
            array(
                'title' => 'Test4',
                'url' => '/news/test4'
            ),
        );

        foreach ($data as &$element) {
            $element = $this->mapper->save(
                $element,
                'overview',
                'sulu_io',
                'en',
                1,
                true,
                null,
                null,
                StructureInterface::STATE_PUBLISHED
            );
        }

        return $data;
    }

    public function testOrderBefore()
    {
        $data = $this->prepareOrderBeforeData();

        $result = $this->nodeRepository->orderBefore($data[3]->getUuid(), $data[0]->getUuid(), 'sulu_io', 'en', 2);
        $this->assertEquals('Test4', $result['title']);
        $this->assertEquals('/test4', $result['path']);
        $this->assertEquals('/news/test4', $result['url']);
        $this->assertEquals(2, $result['changer']);

        $result = $this->nodeRepository->orderBefore($data[2]->getUuid(), $data[3]->getUuid(), 'sulu_io', 'en', 2);
        $this->assertEquals('Test3', $result['title']);
        $this->assertEquals('/test3', $result['path']);
        $this->assertEquals('/news/test3', $result['url']);
        $this->assertEquals(2, $result['changer']);

        $test = $this->nodeRepository->getNodes(null, 'sulu_io', 'en');
        $this->assertEquals(4, sizeof($test['_embedded']['nodes']));
        $nodes = $test['_embedded']['nodes'];

        $this->assertEquals('Test3', $nodes[0]['title']);
        $this->assertEquals('Test4', $nodes[1]['title']);
        $this->assertEquals('Test1', $nodes[2]['title']);
        $this->assertEquals('Test2', $nodes[3]['title']);
    }

    public function testOrderAt()
    {
        $data = $this->prepareOrderBeforeData();

        $result = $this->nodeRepository->orderAt($data[3]->getUuid(), 2, 'sulu_io', 'en', 2);
        $this->assertEquals('Test4', $result['title']);
        $this->assertEquals('/test4', $result['path']);
        $this->assertEquals('/news/test4', $result['url']);
        $this->assertEquals(2, $result['changer']);

        $result = $this->nodeRepository->orderAt($data[0]->getUuid(), 4, 'sulu_io', 'en', 2);
        $this->assertEquals('Test1', $result['title']);
        $this->assertEquals('/test1', $result['path']);
        $this->assertEquals('/news/test1', $result['url']);
        $this->assertEquals(2, $result['changer']);

        $test = $this->nodeRepository->getNodes(null, 'sulu_io', 'en');
        $this->assertEquals(4, sizeof($test['_embedded']['nodes']));
        $nodes = $test['_embedded']['nodes'];

        $this->assertEquals('Test4', $nodes[0]['title']);
        $this->assertEquals('Test2', $nodes[1]['title']);
        $this->assertEquals('Test3', $nodes[2]['title']);
        $this->assertEquals('Test1', $nodes[3]['title']);
    }

    public function testOrderBeforeNonExistingSource()
    {
        $data = $this->prepareOrderBeforeData();
        $this->setExpectedException('Sulu\Component\Rest\Exception\RestException');

        $this->nodeRepository->orderBefore('123-123-123', $data[0]->getUuid(), 'sulu_io', 'en', 2);
    }

    public function testOrderBeforeNonExistingDestination()
    {
        $data = $this->prepareOrderBeforeData();
        $this->setExpectedException('Sulu\Component\Rest\Exception\RestException');

        $this->nodeRepository->orderBefore($data[0]->getUuid(), '123-123-123', 'sulu_io', 'en', 2);
    }

    public function testOrderBeforeInExternalLink()
    {
        $data = $this->prepareOrderBeforeData();

        $newData = array(
            'title' => 'Test4',
            'external_link' => 'www.google.at',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK
        );

        $data[3] = $this->mapper->save(
            $newData,
            'external-link',
            'sulu_io',
            'en',
            1,
            true,
            $data[3]->getUuid(),
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $newData = array(
            'title' => 'Test3',
            'internal_link' => $data[0]->getUuid(),
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK
        );

        $data[2] = $this->mapper->save(
            $newData,
            'internal-link',
            'sulu_io',
            'en',
            1,
            true,
            $data[2]->getUuid(),
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $result = $this->nodeRepository->orderBefore($data[3]->getUuid(), $data[0]->getUuid(), 'sulu_io', 'en', 2);
        $this->assertEquals('Test4', $result['title']);
        $this->assertEquals('/test4', $result['path']);
        $this->assertEquals('www.google.at', $result['external_link']);
        $this->assertEquals(2, $result['changer']);

        $result = $this->nodeRepository->orderBefore($data[2]->getUuid(), $data[3]->getUuid(), 'sulu_io', 'en', 2);
        $this->assertEquals('Test3', $result['title']);
        $this->assertEquals('/test3', $result['path']);
        $this->assertEquals($data[0]->getUuid(), $result['internal_link']);
        $this->assertEquals(2, $result['changer']);

        $test = $this->nodeRepository->getNodes(null, 'sulu_io', 'en');
        $this->assertEquals(4, sizeof($test['_embedded']['nodes']));
        $nodes = $test['_embedded']['nodes'];

        $this->assertEquals('Test3', $nodes[0]['title']);
        $this->assertFalse($nodes[0]['hasSub']);
        $this->assertEquals('Test4', $nodes[1]['title']);
        $this->assertFalse($nodes[0]['hasSub']);
        $this->assertEquals('Test1', $nodes[2]['title']);
        $this->assertFalse($nodes[0]['hasSub']);
        $this->assertEquals('Test2', $nodes[3]['title']);
        $this->assertFalse($nodes[0]['hasSub']);
    }

    public function testCopyLocale()
    {
        $data = array(
            'en' => array(
                'title' => 'Example',
                'url' => '/example'
            )
        );

        $data['en'] = $this->mapper->save(
            $data['en'],
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $this->nodeRepository->copyLocale($data['en']->getUuid(), 1, 'sulu_io', 'en', 'de');

        $result = $this->mapper->load($data['en']->getUuid(), 'sulu_io', 'de')->toArray();
        $this->assertEquals($data['en']->getUuid(), $result['id']);
        $this->assertEquals($data['en']->getPropertyValue('title'), $result['title']);
        $this->assertEquals($data['en']->getPropertyValue('url'), $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);
    }

    public function testCopyMultipleLocales()
    {
        $data = array(
            'en' => array(
                'title' => 'Example',
                'url' => '/example'
            )
        );

        $data['en'] = $this->mapper->save(
            $data['en'],
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $this->nodeRepository->copyLocale($data['en']->getUuid(), 1, 'sulu_io', 'en', array('de', 'de_at'));

        $result = $this->mapper->load($data['en']->getUuid(), 'sulu_io', 'de')->toArray();
        $this->assertEquals($data['en']->getUuid(), $result['id']);
        $this->assertEquals($data['en']->getPropertyValue('title'), $result['title']);
        $this->assertEquals($data['en']->getPropertyValue('url'), $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);

        $result = $this->mapper->load($data['en']->getUuid(), 'sulu_io', 'de_at')->toArray();
        $this->assertEquals($data['en']->getUuid(), $result['id']);
        $this->assertEquals($data['en']->getPropertyValue('title'), $result['title']);
        $this->assertEquals($data['en']->getPropertyValue('url'), $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);
    }

}

class TestExtension extends AbstractExtension
{
    protected $properties = array(
        'a',
        'b'
    );

    public function __construct($name, $additionalPrefix = null)
    {
        $this->name = $name;
        $this->additionalPrefix = $additionalPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        $node->setProperty($this->getPropertyName('a'), $data['a']);
        $node->setProperty($this->getPropertyName('b'), $data['b']);
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        return array(
            'a' => $node->getPropertyValueWithDefault($this->getPropertyName('a'), ''),
            'b' => $node->getPropertyValueWithDefault($this->getPropertyName('b'), '')
        );
    }
}
