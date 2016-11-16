<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Functional\ResourceLocator\Strategy;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Types\ResourceLocator\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\ResourceLocator\Mapper\ResourceLocatorMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class PhpcrMapperTest extends SuluTestCase
{
    /**
     * @var ResourceSegmentBehavior
     */
    private $document1;

    /**
     * @var ResourceSegmentBehavior
     */
    private $document2;

    /**
     * @var ResourceLocatorMapperInterface
     */
    private $phpcrMapper;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var HomeDocument
     */
    private $homeDocument;

    public function setUp()
    {
        $this->initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->documentInspector = $this->getContainer()->get('sulu_document_manager.document_inspector');
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->prepareTestData();

        $this->phpcrMapper = new PhpcrMapper($this->sessionManager, $this->documentManager, $this->documentInspector);
    }

    private function prepareTestData()
    {
        $products = $this->session->getNode('/cmf/sulu_io/routes/de')->addNode('products');
        $products->addMixin('mix:referenceable');

        $machines = $products->addNode('machines');
        $machines->addMixin('mix:referenceable');

        $machines1 = $products->addNode('machines-1');
        $machines1->addMixin('mix:referenceable');

        $drill = $machines->addNode('drill');
        $drill->addMixin('mix:referenceable');

        $drill1 = $machines->addNode('drill-1');
        $drill1->addMixin('mix:referenceable');

        $this->document1 = $this->createDocument($this->homeDocument, 'content1', '/content1');
        $this->documentManager->persist($this->document1, 'de');
        $this->documentManager->publish($this->document1, 'de');

        $this->document2 = $this->createDocument($this->homeDocument, 'content2', '/content2');
        $this->documentManager->persist($this->document2, 'de');
        $this->documentManager->publish($this->document2, 'de');

        $this->documentManager->flush();
    }

    public function testUnique()
    {
        // exists in phpcr
        $result = $this->phpcrMapper->unique('/products/machines', 'sulu_io', 'de');
        $this->assertFalse($result);

        // exists in phpcr
        $result = $this->phpcrMapper->unique('/products/machines/drill', 'sulu_io', 'de');
        $this->assertFalse($result);

        // not exists in phpcr
        $result = $this->phpcrMapper->unique('/products/machines-2', 'sulu_io', 'de');
        $this->assertTrue($result);

        // not exists in phpcr
        $result = $this->phpcrMapper->unique('/products/machines/drill-2', 'sulu_io', 'de');
        $this->assertTrue($result);

        // not exists in phpcr
        $result = $this->phpcrMapper->unique('/news', 'sulu_io', 'de');
        $this->assertTrue($result);
    }

    public function testGetUniquePath()
    {
        // machines & machines-1 exists
        $result = $this->phpcrMapper->getUniquePath('/products/machines', 'sulu_io', 'de');
        $this->assertEquals('/products/machines-2', $result);
        $this->assertTrue($this->phpcrMapper->unique($result, 'sulu_io', 'de'));

        // drill & drill-1 exists
        $result = $this->phpcrMapper->getUniquePath('/products/machines/drill', 'sulu_io', 'de');
        $this->assertEquals('/products/machines/drill-2', $result);
        $this->assertTrue($this->phpcrMapper->unique($result, 'sulu_io', 'de'));

        // products exists
        $result = $this->phpcrMapper->getUniquePath('/products', 'sulu_io', 'de');
        $this->assertEquals('/products-1', $result);
        $this->assertTrue($this->phpcrMapper->unique($result, 'sulu_io', 'de'));

        // news not exists
        $result = $this->phpcrMapper->getUniquePath('/news', 'sulu_io', 'de');
        $this->assertEquals('/news', $result);
        $this->assertTrue($this->phpcrMapper->unique($result, 'sulu_io', 'de'));
    }

    public function testSaveFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException');
        $this->document1->setResourceSegment('/products/machines/drill');
        $this->phpcrMapper->save($this->document1);
    }

    public function testSave()
    {
        $this->document1->setResourceSegment('/products/news/content1-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        $route = '/cmf/sulu_io/routes/de/products/news/content1-news';

        $node = $this->session->getNode($route);
        $this->assertTrue(
            $node->getPropertyValue('sulu:content') == $this->documentInspector->getNode($this->document1)
        );
        $this->assertTrue($node->hasProperty('sulu:content'));
    }

    public function testSaveEmptyRouteNode()
    {
        $childDocument = $this->createDocument($this->homeDocument, 'Test-Child', '/test/child');
        $this->documentManager->persist($childDocument, 'de');
        $this->documentManager->publish($childDocument, 'de');

        $document = $this->createDocument($this->homeDocument, 'Test-Child', '/test');
        $this->documentManager->persist($document, 'de');

        $this->phpcrMapper->save($document);
        $this->sessionManager->getSession()->save();

        $node = $this->session->getNode('/cmf/sulu_io/routes/de/test');
        $this->assertEquals($node->getPropertyValue('sulu:content'), $this->documentInspector->getNode($document));
        $this->assertTrue($node->hasProperty('sulu:history'));
        $this->assertTrue($node->hasProperty('sulu:content'));
    }

    public function testReadFailure()
    {
        $content = $this->session->getNode('/cmf/sulu_io/contents')->addNode('content');
        $content->addMixin('mix:referenceable');
        $this->session->save();

        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->phpcrMapper->loadByContent($content, 'sulu_io', 'de');
    }

    public function testReadFailureUuid()
    {
        $content = $this->session->getNode('/cmf/sulu_io/contents')->addNode('content');
        $content->addMixin('mix:referenceable');
        $this->session->save();

        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->phpcrMapper->loadByContentUuid($content->getIdentifier(), 'sulu_io', 'de');
    }

    public function testRead()
    {
        $this->document1->setResourceSegment('/products/news/content1-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        $result = $this->phpcrMapper->loadByContent($this->documentInspector->getNode($this->document1), 'sulu_io', 'de');
        $this->assertEquals('/products/news/content1-news', $result);
    }

    public function testReadUuid()
    {
        $this->document1->setResourceSegment('/products/news/content1-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        $result = $this->phpcrMapper->loadByContentUuid($this->document1->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/products/news/content1-news', $result);
    }

    public function testLoadFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->phpcrMapper->loadByResourceLocator('/test/test-1', 'sulu_io', 'de');
    }

    public function testLoad()
    {
        // create route for content
        $this->document1->setResourceSegment('/products/news/content1-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        $result = $this->phpcrMapper->loadByResourceLocator('/products/news/content1-news', 'sulu_io', 'de');
        $this->assertEquals($this->document1->getUuid(), $result);
    }

    public function testMove()
    {
        // create route for content
        $this->document1->setResourceSegment('/products/news/content1-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        // move
        $this->document1->setResourceSegment('/products/asdf/content2-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        $oldNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/news/content1-news');
        $newNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/asdf/content2-news');

        $oldNodeMixins = $oldNode->getMixinNodeTypes();
        $newNodeMixins = $newNode->getMixinNodeTypes();

        $this->assertEquals('sulu:path', $newNodeMixins[0]->getName());
        $this->assertEquals('sulu:path', $oldNodeMixins[0]->getName());

        $this->assertTrue($oldNode->getPropertyValue('sulu:history'));
        $this->assertEquals($newNode, $oldNode->getPropertyValue('sulu:content'));
        $this->assertEquals(
            $this->documentInspector->getNode($this->document1),
            $newNode->getPropertyValue('sulu:content')
        );

        // get content from new path
        $result = $this->phpcrMapper->loadByResourceLocator('/products/asdf/content2-news', 'sulu_io', 'de');
        $this->assertEquals($this->document1->getUuid(), $result);

        // get content from history should throw an exception
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorMovedException');
        $this->phpcrMapper->loadByResourceLocator('/products/news/content1-news', 'sulu_io', 'de');
    }

    public function testMoveTwice()
    {
        // create route for content
        $this->document1->setResourceSegment('/products/news/content1-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        // first move
        $this->document1->setResourceSegment('/products/news/content2-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        // second move
        $this->document1->setResourceSegment('/products/asdf/content2-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        $oldNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/news/content1-news');
        $newNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/asdf/content2-news');

        $oldNodeMixins = $oldNode->getMixinNodeTypes();
        $newNodeMixins = $newNode->getMixinNodeTypes();

        $this->assertEquals('sulu:path', $newNodeMixins[0]->getName());
        // FIXME after change mixin works: $this->assertEquals('sulu:history', $oldNodeMixins[0]->getName());

        $this->assertEquals($newNode, $oldNode->getPropertyValue('sulu:content'));
        $this->assertEquals(
            $this->documentInspector->getNode($this->document1),
            $newNode->getPropertyValue('sulu:content')
        );

        // get content from new path
        $result = $this->phpcrMapper->loadByResourceLocator('/products/asdf/content2-news', 'sulu_io', 'de');
        $this->assertEquals($this->document1->getUuid(), $result);

        // get content from history should throw an exception
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorMovedException');
        $this->phpcrMapper->loadByResourceLocator('/products/news/content1-news', 'sulu_io', 'de');
    }

    public function testGetParentPath()
    {
        $session = $this->sessionManager->getSession();

        $document2 = $this->createDocument($this->document1, 'content2', '/news');
        $this->documentManager->persist($document2, 'en');
        $this->documentManager->publish($document2, 'en');
        $document3 = $this->createDocument($document2, 'content3', '/news/news-1');
        $this->documentManager->persist($document3, 'en');
        $this->documentManager->publish($document3, 'en');
        $document4 = $this->createDocument($document3, 'content4', '/news/news-1/sub-1');
        $this->documentManager->persist($document4, 'en');
        $this->documentManager->publish($document4, 'en');
        $this->documentManager->flush();

        $this->document1->setResourceSegment('/news/news-1/sub-2');
        $this->phpcrMapper->save($this->document1);

        $this->document1->setResourceSegment('/news/news-2');
        $this->phpcrMapper->save($this->document1);
        $this->document1->setResourceSegment('/news/news-2/sub-1');
        $this->phpcrMapper->save($this->document1);
        $this->document1->setResourceSegment('/news/news-2/sub-2');
        $this->phpcrMapper->save($this->document1);
        $session->save();

        $result = $this->phpcrMapper->getParentPath($document4->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('/news/news-1', $result);

        $result = $this->phpcrMapper->getParentPath($document4->getUuid(), 'sulu_io', 'de');
        $this->assertNull($result);
    }

    public function testLoadHistoryByContentUuid()
    {
        sleep(1);

        // create route for content
        $this->document1->setResourceSegment('/products/news/content1-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->document1->setResourceSegment('/products/asdf/content2-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->document1->setResourceSegment('/products/content2-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->document1->setResourceSegment('/content2-news');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->document1->setResourceSegment('/best-ur-ever');
        $this->phpcrMapper->save($this->document1);
        $this->sessionManager->getSession()->save();

        $result = $this->phpcrMapper->loadHistoryByContentUuid($this->document1->getUuid(), 'sulu_io', 'de');

        $this->assertEquals(5, count($result));
        $this->assertEquals('/content2-news', $result[0]->getResourceLocator());
        $this->assertEquals('/products/content2-news', $result[1]->getResourceLocator());
        $this->assertEquals('/products/asdf/content2-news', $result[2]->getResourceLocator());
        $this->assertEquals('/products/news/content1-news', $result[3]->getResourceLocator());
        $this->assertEquals('/content1', $result[4]->getResourceLocator());

        $this->assertTrue($result[0]->getCreated() > $result[1]->getCreated());
        $this->assertTrue($result[1]->getCreated() > $result[2]->getCreated());
        $this->assertTrue($result[2]->getCreated() > $result[3]->getCreated());
        $this->assertTrue($result[3]->getCreated() > $result[4]->getCreated());
    }

    public function testLoadHistoryByContentUuidWithoutRoutes()
    {
        $document = $this->createDocument($this->homeDocument, 'Test', '/test');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $this->assertEmpty($this->phpcrMapper->loadHistoryByContentUuid($document->getUuid(), 'sulu_io', 'de'));
    }

    public function testDeleteByPath()
    {
        $session = $this->sessionManager->getSession();
        $rootNode = $session->getNode('/cmf/sulu_io/routes/de');

        // create routes for content
        $this->document1->setResourceSegment('/news');
        $this->phpcrMapper->save($this->document1);
        $session->save();
        $session->refresh(false);

        // move route
        $this->document1->setResourceSegment('/test');
        $this->phpcrMapper->save($this->document1);
        $session->save();
        $session->refresh(false);

        $this->assertTrue($rootNode->hasNode('content1'));
        $this->assertTrue($rootNode->hasNode('news'));
        $this->assertTrue($rootNode->hasNode('test'));

        // delete a history url
        $this->phpcrMapper->deleteByPath('/content1', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);
        $this->assertFalse($rootNode->hasNode('content1'));
        $this->assertTrue($rootNode->hasNode('news'));
        $this->assertTrue($rootNode->hasNode('test'));

        // delete a normal url
        $this->phpcrMapper->deleteByPath('/test', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);
        $this->assertFalse($rootNode->hasNode('content1'));
        $this->assertFalse($rootNode->hasNode('news'));
        $this->assertFalse($rootNode->hasNode('test'));
    }

    public function provideInvalidDeleteByPathArguments()
    {
        return [
            [''],
            ['/'],
            [null],
        ];
    }

    /**
     * The deleteByPath method would delete the entire route tree for the given language, if an invalid path is passed.
     *
     * @dataProvider provideInvalidDeleteByPathArguments
     */
    public function testDeleteByPathWithInvalidArguments($invalidPath)
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->phpcrMapper->deleteByPath($invalidPath, 'sulu_io', 'en');
    }

    public function testTreeDeleteByPath()
    {
        $session = $this->sessionManager->getSession();
        $rootNode = $session->getNode('/cmf/sulu_io/routes/de');

        $childDocument = $this->createDocument($this->document1, 'test', '/news/test');
        $this->documentManager->persist($childDocument, 'de');
        $this->documentManager->flush();
        $session->save();
        $session->refresh(false);

        // move route
        $this->document1->setResourceSegment('/test');
        $this->phpcrMapper->save($this->document1);
        $session->save();
        $session->refresh(false);

        // delete all
        $this->phpcrMapper->deleteByPath('/test', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);
        $this->assertFalse($rootNode->hasNode('test'));
        $this->assertFalse($rootNode->hasNode('content1'));
    }

    private function createDocument($parentDocument, $title, $url)
    {
        $document = $this->documentManager->create('page');
        $document->setTitle($title);
        $document->setParent($parentDocument);
        $document->setStructureType('default');
        $document->setResourceSegment($url);
        $document->setExtensionsData(
            ['excerpt' => ['title' => '', 'description' => '', 'categories' => [], 'tags' => []]]
        );

        return $document;
    }
}
