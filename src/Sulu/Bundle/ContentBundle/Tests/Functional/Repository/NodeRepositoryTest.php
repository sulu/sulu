<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Repository;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

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
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var ExtensionInterface[]
     */
    private $extensions;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    protected function setUp()
    {
        $this->initPhpcr();
        $this->extensions = [new TestExtension('test1', 'test1')];
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->nodeRepository = $this->getContainer()->get('sulu_content.node_repository');
        $this->extensionManager = $this->getContainer()->get('sulu_content.extension.manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->extensionManager->addExtension(new TestExtension('test1', 'test1'));
    }

    private function prepareGetTestData()
    {
        $data = [
            'title' => 'Testtitle',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'Test',
        ];

        return $this->save($data, 'overview', 'sulu_io', 'en', 1, true, null, null, Structure::STATE_PUBLISHED);
    }

    public function testGet()
    {
        $document = $this->prepareGetTestData();

        $result = $this->nodeRepository->getNode($document->getUuid(), 'sulu_io', 'en');

        $this->assertEquals($document->getStructure()->getProperty('title')->getValue(), $result['title']);
        $this->assertEquals($document->getStructure()->getProperty('url')->getValue(), $result['url']);
    }

    public function testDelete()
    {
        $structure = $this->prepareGetTestData();

        $this->nodeRepository->deleteNode($structure->getUuid(), 'sulu_io');

        $this->setExpectedException(DocumentNotFoundException::class);
        $this->nodeRepository->getNode($structure->getUuid(), 'sulu_io', 'en');
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
        $this->assertEquals('Test CMF', $result['_embedded']['nodes'][1]['title']);
    }

    /**
     * It should load the node tree tiers up until the tier containing the given UUID.
     */
    public function testGetNodesTreeUntilGivenUuid()
    {
        $structures = $this->prepareGetTreeTestData();
        $structure = $structures[2];
        $this->assertEquals('Child 1', $structure->getTitle());

        $result = $this->nodeRepository->getNodesTree($structure->getUuid(), 'sulu_io', 'en', false, false, false);
        $this->assertEquals(2, count($result['_embedded']['nodes']));
    }

    /**
     * It should load the node tree tiers up until the tier containing the given UUID
     * Without a webspte.
     */
    public function testGetNodesTreeWithoutWebspace()
    {
        $structures = $this->prepareGetTreeTestData();
        $structure = $structures[0];

        $result = $this->nodeRepository->getNodesTree($structure->getUuid(), 'sulu_io', 'en', false, false, false);
        $this->assertEquals(2, count($result['_embedded']['nodes']));
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
        $this->assertTrue($result['_embedded']['nodes'][0]['hasSub']);
    }

    /**
     * With a webspace.
     */
    public function testGetNodesTreeWithWebspace()
    {
        $structures = $this->prepareGetTreeTestData();
        $structure = $structures[0];

        $result = $this->nodeRepository->getNodesTree($structure->getUuid(), 'sulu_io', 'en', false, false, true);
        $this->assertEquals(1, count($result['_embedded']['nodes']));
        $webspace = $result['_embedded']['nodes'][0];
        $this->assertEquals('Sulu CMF', $webspace['title']);
        $this->assertEquals('/', $webspace['path']);
        $this->assertTrue($webspace['hasSub']);

        $this->assertEquals(2, count($webspace['_embedded']['nodes']));
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['_embedded']['nodes'][0]['path']);
        $this->assertTrue($result['_embedded']['nodes'][0]['_embedded']['nodes'][0]['hasSub']);
    }

    /**
     * It should get the node tree tiers with ghosts.
     */
    public function testGetNodesTreeWithGhosts()
    {
        $structures = $this->prepareGetTreeTestData();
        $structure = $structures[0];

        $result = $this->nodeRepository->getNodesTree($structure->getUuid(), 'sulu_io', 'de', false, false, false);
        $this->assertEquals(2, count($result['_embedded']['nodes']));
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
        $this->assertEquals('ghost', $result['_embedded']['nodes'][0]['type']['name']);
        $this->assertEquals('en', $result['_embedded']['nodes'][0]['type']['value']);
        $this->assertTrue($result['_embedded']['nodes'][0]['hasSub']);
    }

    private function prepareGetTreeTestData()
    {
        $data = [
            'title' => 'Testtitle',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'Test',
        ];

        $structures = [];
        $structures[] = $this->save($data, 'overview', 'sulu_io', 'en', 1, true, null, null, Structure::STATE_PUBLISHED);

        $data['title'] = 'Other title';
        $data['url'] = '/other';

        $structures[] = $this->save($data, 'overview', 'sulu_io', 'en', 1, true, null, null, Structure::STATE_PUBLISHED);

        $data['title'] = 'Child 1';
        $data['url'] = '/other/child1';

        $structures[] = $this->save($data, 'overview', 'sulu_io', 'en', 1, true, null, $structures[0]->getUuid(), Structure::STATE_PUBLISHED);

        return $structures;
    }

    public function testExtensionData()
    {
        $data = $this->prepareGetTestData();
        $extData = ['a' => 'A', 'b' => 'B'];

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

        $result = $this->nodeRepository->getNodesByIds([], 'sulu_io', 'en');
        $this->assertEquals(0, count($result['_embedded']['nodes']));
        $this->assertEquals(0, $result['total']);

        $result = $this->nodeRepository->getNodesByIds(
            [
                $data->getUuid(),
            ],
            'sulu_io',
            'en'
        );
        $this->assertEquals(1, count($result['_embedded']['nodes']));
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
    }

    public function testGetByIdsNotExisitingID()
    {
        $data = $this->prepareGetTestData();

        $result = $this->nodeRepository->getNodesByIds([], 'sulu_io', 'en');
        $this->assertEquals(0, count($result['_embedded']['nodes']));
        $this->assertEquals(0, $result['total']);

        $result = $this->nodeRepository->getNodesByIds(
            [
                $data->getUuid(),
                '556ce63c-97a3-4a03-81a9-719bc01234e6',
            ],
            'sulu_io',
            'en'
        );
        $this->assertEquals(1, count($result['_embedded']['nodes']));
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('Testtitle', $result['_embedded']['nodes'][0]['title']);
        $this->assertEquals('/testtitle', $result['_embedded']['nodes'][0]['path']);
    }

    public function testGetFilteredNodesInOrder()
    {
        $data = [
            [
                'title' => 'Testtitle1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test1',
                'article' => 'Test',
            ],
            [
                'title' => 'Testtitle2',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test2',
                'article' => 'Test',
            ],
        ];

        foreach ($data as &$element) {
            $element = $this->save(
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
            ['sortBy' => ['published'], 'sortMethod' => 'asc'],
            'en',
            'sulu_io'
        );

        $this->assertEquals('Testtitle1', $nodes[0]['title']);
        $this->assertEquals('Testtitle2', $nodes[1]['title']);

        $nodes = $this->nodeRepository->getFilteredNodes(
            ['sortBy' => ['published'], 'sortMethod' => 'desc'],
            'en',
            'sulu_io'
        );

        $this->assertEquals('Testtitle2', $nodes[0]['title']);
        $this->assertEquals('Testtitle1', $nodes[1]['title']);
    }

    public function testGetFilteredNodesInOrderByTitle()
    {
        $data = [
            [
                'title' => 'hello you',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test1',
                'article' => 'Test',
            ],
            [
                'title' => 'Hello me',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test2',
                'article' => 'Test',
            ],
            [
                'title' => 'Test',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test3',
                'article' => 'Test',
            ],
        ];

        foreach ($data as &$element) {
            $element = $this->save(
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
            ['sortBy' => ['title'], 'sortMethod' => 'asc'],
            'en',
            'sulu_io'
        );

        $this->assertEquals('Hello me', $nodes[0]['title']);
        $this->assertEquals('hello you', $nodes[1]['title']);
        $this->assertEquals('Test', $nodes[2]['title']);

        $nodes = $this->nodeRepository->getFilteredNodes(
            ['sortBy' => ['title'], 'sortMethod' => 'desc'],
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
    private function prepareOrderBeforeData()
    {
        $data = [
            [
                'title' => 'Test1',
                'url' => '/news/test1',
            ],
            [
                'title' => 'Test2',
                'url' => '/news/test2',
            ],
            [
                'title' => 'Test3',
                'url' => '/news/test3',
            ],
            [
                'title' => 'Test4',
                'url' => '/news/test4',
            ],
        ];

        foreach ($data as &$element) {
            $element = $this->save(
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

    public function testOrderAt()
    {
        $data = $this->prepareOrderBeforeData();

        $result = $this->nodeRepository->orderAt($data[3]->getUuid(), 2, 'sulu_io', 'en', 2);
        $this->assertEquals('Test4', $result['title']);
        $this->assertEquals('/test4', $result['path']);
        $this->assertEquals('/news/test4', $result['url']);

        $result = $this->nodeRepository->orderAt($data[0]->getUuid(), 4, 'sulu_io', 'en', 2);
        $this->assertEquals('Test1', $result['title']);
        $this->assertEquals('/test1', $result['path']);
        $this->assertEquals('/news/test1', $result['url']);

        $test = $this->nodeRepository->getNodes(null, 'sulu_io', 'en');
        $this->assertEquals(4, count($test['_embedded']['nodes']));
        $nodes = $test['_embedded']['nodes'];

        $this->assertEquals('Test4', $nodes[0]['title']);
        $this->assertEquals('Test2', $nodes[1]['title']);
        $this->assertEquals('Test3', $nodes[2]['title']);
        $this->assertEquals('Test1', $nodes[3]['title']);
    }

    public function testCopyLocale()
    {
        $document = $this->save(
            [
                'title' => 'Example',
                'url' => '/example',
            ],
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $this->nodeRepository->copyLocale($document->getUuid(), 1, 'sulu_io', 'en', 'de');

        $result = $this->mapper->load($document->getUuid(), 'sulu_io', 'de')->toArray();
        $this->assertEquals($document->getUuid(), $result['id']);
        $this->assertEquals('Example', $result['title']);
        $this->assertEquals('/example', $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);
    }

    public function testCopyMultipleLocales()
    {
        $document = $this->save(
            [
                'title' => 'Example',
                'url' => '/example',
            ],
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED
        );

        $this->nodeRepository->copyLocale($document->getUuid(), 1, 'sulu_io', 'en', ['de', 'de_at']);

        $result = $this->mapper->load($document->getUuid(), 'sulu_io', 'de')->toArray();
        $this->assertEquals($document->getUuid(), $result['id']);
        $this->assertEquals('Example', $result['title']);
        $this->assertEquals('/example', $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);

        $result = $this->mapper->load($document->getUuid(), 'sulu_io', 'de_at')->toArray();
        $this->assertEquals($document->getUuid(), $result['id']);
        $this->assertEquals('Example', $result['title']);
        $this->assertEquals('/example', $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);
    }

    private function save(
        $data,
        $structureType,
        $webspaceKey,
        $locale,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null,
        $documentAlias = Structure::TYPE_PAGE
    ) {
        try {
            $document = $this->documentManager->find($uuid, $locale);
        } catch (DocumentNotFoundException $e) {
            $document = $this->documentManager->create($documentAlias);
        }
        $document->setTitle($data['title']);
        $document->getStructure()->bind($data);
        $document->setStructureType($structureType);

        if ($document instanceof ShadowLocaleBehavior) {
            $document->setShadowLocale($shadowBaseLanguage);
            $document->setShadowLocaleEnabled($isShadow);
        }

        if ($state === null) {
            $state = WorkflowStage::TEST;
        }
        $document->setWorkflowStage($state);

        if (isset($data['url']) && $document instanceof ResourceSegmentBehavior) {
            $document->setResourceSegment($data['url']);
        }

        if (isset($data['navContexts'])) {
            $document->setNavigationContexts($data['navContexts']);
        }

        if (isset($data['nodeType'])) {
            $document->setRedirectType($data['nodeType']);
        }

        if (isset($data['internal_link'])) {
            $document->setRedirectTarget($this->documentManager->find($data['internal_link'], $locale));
        }

        if (isset($data['external'])) {
            $document->setRedirectExternal($data['external']);
        }

        if ($document instanceof ExtensionBehavior) {
            if (isset($data['ext'])) {
                $document->setExtensionsData($data['ext']);
            } else {
                $document->setExtensionsData([]);
            }
        }

        $persistOptions = [];
        if ($parentUuid) {
            $document->setParent($this->documentManager->find($parentUuid, $locale));
        } elseif (!$document->getParent()) {
            $persistOptions['parent_path'] = '/cmf/' . $webspaceKey . '/contents';
        }

        $this->documentManager->persist($document, $locale, $persistOptions);
        if ($state === WorkflowStage::PUBLISHED) {
            $this->documentManager->publish($document, $locale);
        }
        $this->documentManager->flush();

        return $document;
    }
}

class TestExtension extends AbstractExtension
{
    protected $properties = [
        'a',
        'b',
    ];

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
        return [
            'a' => $node->getPropertyValueWithDefault($this->getPropertyName('a'), ''),
            'b' => $node->getPropertyValueWithDefault($this->getPropertyName('b'), ''),
        ];
    }
}
