<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Functional\Rlp\Strategy;

use PHPCR\NodeInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Types\Rlp\Mapper\PhpcrMapper;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;

class PhpcrMapperTest extends SuluTestCase
{
    /**
     * @var NodeInterface
     */
    private $content1;

    /**
     * @var NodeInterface
     */
    private $content2;

    /**
     * @var RlpMapperInterface
     */
    private $rlpMapper;

    public function setUp()
    {
        $this->initPhpcr();
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');

        $this->prepareTestData();

        $this->rlpMapper = new PhpcrMapper($this->sessionManager, '/cmf/routes');
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

        $contents = $this->session->getNode('/cmf/sulu_io/contents');
        $this->content1 = $contents->addNode('content1');
        $this->content1->addMixin('mix:referenceable');

        $this->content2 = $contents->addNode('content2');
        $this->content2->addMixin('mix:referenceable');

        $this->session->save();
    }

    public function testUnique()
    {
        // exists in phpcr
        $result = $this->rlpMapper->unique('/products/machines', 'sulu_io', 'de');
        $this->assertFalse($result);

        // exists in phpcr
        $result = $this->rlpMapper->unique('/products/machines/drill', 'sulu_io', 'de');
        $this->assertFalse($result);

        // not exists in phpcr
        $result = $this->rlpMapper->unique('/products/machines-2', 'sulu_io', 'de');
        $this->assertTrue($result);

        // not exists in phpcr
        $result = $this->rlpMapper->unique('/products/machines/drill-2', 'sulu_io', 'de');
        $this->assertTrue($result);

        // not exists in phpcr
        $result = $this->rlpMapper->unique('/news', 'sulu_io', 'de');
        $this->assertTrue($result);
    }

    public function testGetUniquePath()
    {
        // machines & machines-1 exists
        $result = $this->rlpMapper->getUniquePath('/products/machines', 'sulu_io', 'de');
        $this->assertEquals('/products/machines-2', $result);
        $this->assertTrue($this->rlpMapper->unique($result, 'sulu_io', 'de'));

        // drill & drill-1 exists
        $result = $this->rlpMapper->getUniquePath('/products/machines/drill', 'sulu_io', 'de');
        $this->assertEquals('/products/machines/drill-2', $result);
        $this->assertTrue($this->rlpMapper->unique($result, 'sulu_io', 'de'));

        // products exists
        $result = $this->rlpMapper->getUniquePath('/products', 'sulu_io', 'de');
        $this->assertEquals('/products-1', $result);
        $this->assertTrue($this->rlpMapper->unique($result, 'sulu_io', 'de'));

        // news not exists
        $result = $this->rlpMapper->getUniquePath('/news', 'sulu_io', 'de');
        $this->assertEquals('/news', $result);
        $this->assertTrue($this->rlpMapper->unique($result, 'sulu_io', 'de'));
    }

    public function testSaveFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException');
        $this->rlpMapper->save($this->content1, '/products/machines/drill', 'sulu_io', 'de');
    }

    public function testSave()
    {
        $this->rlpMapper->save($this->content1, '/products/news/content1-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $route = '/cmf/sulu_io/routes/de/products/news/content1-news';

        $node = $this->session->getNode($route);
        $this->assertTrue($node->getPropertyValue('sulu:content') == $this->content1);
        $this->assertTrue($node->hasProperty('sulu:content'));
    }

    public function testReadFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->rlpMapper->loadByContent($this->content1, 'sulu_io', 'de');
    }

    public function testReadFailureUuid()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->rlpMapper->loadByContentUuid($this->content1->getIdentifier(), 'sulu_io', 'de');
    }

    public function testRead()
    {
        $this->rlpMapper->save($this->content1, '/products/news/content1-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $result = $this->rlpMapper->loadByContent($this->content1, 'sulu_io', 'de');
        $this->assertEquals('/products/news/content1-news', $result);
    }

    public function testReadUuid()
    {
        $this->rlpMapper->save($this->content1, '/products/news/content1-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $result = $this->rlpMapper->loadByContentUuid($this->content1->getIdentifier(), 'sulu_io', 'de');
        $this->assertEquals('/products/news/content1-news', $result);
    }

    public function testLoadFailure()
    {
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->rlpMapper->loadByResourceLocator('/test/test-1', 'sulu_io', 'de');
    }

    public function testLoad()
    {
        // create route for content
        $this->rlpMapper->save($this->content1, '/products/news/content1-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $result = $this->rlpMapper->loadByResourceLocator('/products/news/content1-news', 'sulu_io', 'de');
        $this->assertEquals($this->content1->getIdentifier(), $result);
    }

    public function testMove()
    {
        // create route for content
        $this->rlpMapper->save($this->content1, '/products/news/content1-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        // move
        $this->rlpMapper->move('/products/news/content1-news', '/products/asdf/content2-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $oldNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/news/content1-news');
        $newNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/asdf/content2-news');

        $oldNodeMixins = $oldNode->getMixinNodeTypes();
        $newNodeMixins = $newNode->getMixinNodeTypes();

        $this->assertEquals('sulu:path', $newNodeMixins[0]->getName());
        $this->assertEquals('sulu:path', $oldNodeMixins[0]->getName());

        $this->assertTrue($oldNode->getPropertyValue('sulu:history'));
        $this->assertEquals($newNode, $oldNode->getPropertyValue('sulu:content'));
        $this->assertEquals($this->content1, $newNode->getPropertyValue('sulu:content'));

        // get content from new path
        $result = $this->rlpMapper->loadByResourceLocator('/products/asdf/content2-news', 'sulu_io', 'de');
        $this->assertEquals($this->content1->getIdentifier(), $result);

        // get content from history should throw an exception
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorMovedException');
        $result = $this->rlpMapper->loadByResourceLocator('/products/news/content1-news', 'sulu_io', 'de');
    }

    public function testMoveTwice()
    {
        // create route for content
        $this->rlpMapper->save($this->content1, '/products/news/content1-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        // first move
        $this->rlpMapper->move('/products/news/content1-news', '/products/news/content2-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        // second move
        $this->rlpMapper->move('/products/news/content2-news', '/products/asdf/content2-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $oldNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/news/content1-news');
        $newNode = $this->session->getNode('/cmf/sulu_io/routes/de/products/asdf/content2-news');

        $oldNodeMixins = $oldNode->getMixinNodeTypes();
        $newNodeMixins = $newNode->getMixinNodeTypes();

        $this->assertEquals('sulu:path', $newNodeMixins[0]->getName());
        // FIXME after change mixin works: $this->assertEquals('sulu:history', $oldNodeMixins[0]->getName());

        $this->assertEquals($newNode, $oldNode->getPropertyValue('sulu:content'));
        $this->assertEquals($this->content1, $newNode->getPropertyValue('sulu:content'));

        // get content from new path
        $result = $this->rlpMapper->loadByResourceLocator('/products/asdf/content2-news', 'sulu_io', 'de');
        $this->assertEquals($this->content1->getIdentifier(), $result);

        // get content from history should throw an exception
        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorMovedException');
        $result = $this->rlpMapper->loadByResourceLocator('/products/news/content1-news', 'sulu_io', 'de');
    }

    public function testMoveNotExist()
    {
        // create routes for content
        $this->rlpMapper->save($this->content1, '/news/news-1', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $this->setExpectedException('Sulu\Component\Content\Exception\ResourceLocatorNotFoundException');
        $this->rlpMapper->move('/news', '/neuigkeiten', 'sulu_io', 'de');
    }

    public function testMoveTree()
    {
        $session = $this->sessionManager->getSession();

        // create routes for content
        $this->rlpMapper->save($this->content1, '/news', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1/sub-1', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1/sub-2', 'sulu_io', 'de');

        $this->rlpMapper->save($this->content1, '/news/news-2', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-2/sub-1', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-2/sub-2', 'sulu_io', 'de');
        $session->save();

        // move route
        $this->rlpMapper->move('/news', '/test', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);

        // check exist new routes
        $this->assertEquals(
            $this->content1->getIdentifier(),
            $this->rlpMapper->loadByResourceLocator('/test', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $this->content1->getIdentifier(),
            $this->rlpMapper->loadByResourceLocator('/test/news-1', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $this->content1->getIdentifier(),
            $this->rlpMapper->loadByResourceLocator('/test/news-1/sub-1', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $this->content1->getIdentifier(),
            $this->rlpMapper->loadByResourceLocator('/test/news-1/sub-2', 'sulu_io', 'de')
        );

        $this->assertEquals(
            $this->content1->getIdentifier(),
            $this->rlpMapper->loadByResourceLocator('/test/news-2', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $this->content1->getIdentifier(),
            $this->rlpMapper->loadByResourceLocator('/test/news-2/sub-1', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $this->content1->getIdentifier(),
            $this->rlpMapper->loadByResourceLocator('/test/news-2/sub-2', 'sulu_io', 'de')
        );

        // check history
        $this->assertEquals('/test', $this->getRlForHistory('/news'));
        $this->assertEquals('/test/news-1', $this->getRlForHistory('/news/news-1'));
        $this->assertEquals('/test/news-1/sub-1', $this->getRlForHistory('/news/news-1/sub-1'));
        $this->assertEquals('/test/news-1/sub-2', $this->getRlForHistory('/news/news-1/sub-2'));

        $this->assertEquals('/test/news-2', $this->getRlForHistory('/news/news-2'));
        $this->assertEquals('/test/news-2/sub-1', $this->getRlForHistory('/news/news-2/sub-1'));
        $this->assertEquals('/test/news-2/sub-2', $this->getRlForHistory('/news/news-2/sub-2'));
    }

    private function getRlForHistory($rl)
    {
        try {
            $this->rlpMapper->loadByResourceLocator($rl, 'sulu_io', 'de');

            return false;
        } catch (ResourceLocatorMovedException $ex) {
            return $ex->getNewResourceLocator();
        }
    }

    public function testGetParentPath()
    {
        $session = $this->sessionManager->getSession();

        $c1 = $this->content1;
        $c2 = $c1->addNode('content2');
        $c2->addMixin('mix:referenceable');
        $session->save();
        $c3 = $c2->addNode('content3');
        $c3->addMixin('mix:referenceable');
        $session->save();
        $c4 = $c3->addNode('content4');
        $c4->addMixin('mix:referenceable');
        $session->save();

        // create routes for content
        $this->rlpMapper->save($c2, '/news', 'sulu_io', 'de');
        $this->rlpMapper->save($c3, '/news/news-1', 'sulu_io', 'de');
        $this->rlpMapper->save($c4, '/news/news-1/sub-1', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1/sub-2', 'sulu_io', 'de');

        $this->rlpMapper->save($this->content1, '/news/news-2', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-2/sub-1', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-2/sub-2', 'sulu_io', 'de');
        $session->save();

        $result = $this->rlpMapper->getParentPath($c4->getIdentifier(), 'sulu_io', 'de');
        $this->assertEquals('/news/news-1', $result);

        $result = $this->rlpMapper->getParentPath($c4->getIdentifier(), 'sulu_io', 'en');
        $this->assertNull($result);
    }

    public function testLoadHistoryByContentUuid()
    {
        // create route for content
        $this->rlpMapper->save($this->content1, '/products/news/content1-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->rlpMapper->move('/products/news/content1-news', '/products/asdf/content2-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->rlpMapper->move('/products/asdf/content2-news', '/products/content2-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->rlpMapper->move('/products/content2-news', '/content2-news', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        sleep(1);

        // move
        $this->rlpMapper->move('/content2-news', '/best-url-ever', 'sulu_io', 'de');
        $this->sessionManager->getSession()->save();

        $result = $this->rlpMapper->loadHistoryByContentUuid($this->content1->getIdentifier(), 'sulu_io', 'de');

        $this->assertEquals(4, count($result));
        $this->assertEquals('/content2-news', $result[0]->getResourceLocator());
        $this->assertEquals('/products/content2-news', $result[1]->getResourceLocator());
        $this->assertEquals('/products/asdf/content2-news', $result[2]->getResourceLocator());
        $this->assertEquals('/products/news/content1-news', $result[3]->getResourceLocator());

        $this->assertTrue($result[0]->getCreated() > $result[1]->getCreated());
        $this->assertTrue($result[1]->getCreated() > $result[2]->getCreated());
        $this->assertTrue($result[2]->getCreated() > $result[3]->getCreated());
    }

    public function testDeleteByPath()
    {
        $session = $this->sessionManager->getSession();
        $rootNode = $session->getNode('/cmf/sulu_io/routes/de');

        // create routes for content
        $this->rlpMapper->save($this->content1, '/news', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1/sub-1', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1/sub-2', 'sulu_io', 'de');

// FIXME  issue: https://github.com/jackalope/jackalope/issues/227
// FIXME  pr: https://github.com/jackalope/jackalope/pull/228
//        $this->rlpMapper->save($this->content1, '/news/news-2', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-2/sub-1', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-2/sub-2', 'sulu_io', 'de');
        $session->save();

        // move route
        $this->rlpMapper->move('/news', '/test', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);

        // delete a history url
        $this->rlpMapper->deleteByPath('/news/news-1/sub-1', 'sulu_io', 'de');
        $this->assertFalse($rootNode->hasNode('news/news-1/sub-1'));
        $this->assertTrue($rootNode->hasNode('news/news-1/sub-2'));
        $this->assertTrue($rootNode->hasNode('test/news-1/sub-1'));
        $this->assertTrue($rootNode->hasNode('test/news-1/sub-2'));

        // delete a normal url
        $this->rlpMapper->deleteByPath('/test/news-1/sub-2', 'sulu_io', 'de');
        $this->assertFalse($rootNode->hasNode('news/news-1/sub-1'));
        $this->assertFalse($rootNode->hasNode('news/news-1/sub-2'));

        $this->assertTrue($rootNode->hasNode('news/news-1'));
        $this->assertTrue($rootNode->hasNode('test/news-1/sub-1'));
        $this->assertFalse($rootNode->hasNode('test/news-1/sub-2'));
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
        $this->rlpMapper->deleteByPath($invalidPath, 'sulu_io', 'en');
    }

    public function testTreeDeleteByPath()
    {
        $session = $this->sessionManager->getSession();
        $rootNode = $session->getNode('/cmf/sulu_io/routes/de');

        // create routes for content
        $this->rlpMapper->save($this->content1, '/news', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1', 'sulu_io', 'de');

// FIXME  issue: https://github.com/jackalope/jackalope/issues/227
// FIXME  pr: https://github.com/jackalope/jackalope/pull/228
//        $this->rlpMapper->save($this->content1, '/news/news-1/sub-1', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-1/sub-2', 'sulu_io', 'de');
//
//        $this->rlpMapper->save($this->content1, '/news/news-2', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-2/sub-1', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-2/sub-2', 'sulu_io', 'de');
        $session->save();

        // move route
        $this->rlpMapper->move('/news', '/test', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);

        // delete all
        $this->rlpMapper->deleteByPath('/test', 'sulu_io', 'de');
        $this->assertFalse($rootNode->hasNode('test'));
        $this->assertFalse($rootNode->hasNode('news'));
    }

    public function testRestore()
    {
        $session = $this->sessionManager->getSession();
        $rootNode = $session->getNode('/cmf/sulu_io/routes/de');

        // create routes for content
        $this->rlpMapper->save($this->content2, '/news', 'sulu_io', 'de');
        $this->rlpMapper->save($this->content1, '/news/news-1', 'sulu_io', 'de');

// FIXME  issue: https://github.com/jackalope/jackalope/issues/227
// FIXME  pr: https://github.com/jackalope/jackalope/pull/228
//        $this->rlpMapper->save($this->content1, '/news/news-1/sub-1', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-1/sub-2', 'sulu_io', 'de');
//
//        $this->rlpMapper->save($this->content1, '/news/news-2', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-2/sub-1', 'sulu_io', 'de');
//        $this->rlpMapper->save($this->content1, '/news/news-2/sub-2', 'sulu_io', 'de');
        $session->save();

        // move route
        $this->rlpMapper->move('/news', '/asdf', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);

        // move route
        $this->rlpMapper->move('/asdf', '/test', 'sulu_io', 'de');
        $session->save();
        $session->refresh(false);

        // load history
        $result = $this->rlpMapper->loadHistoryByContentUuid($this->content2->getIdentifier(), 'sulu_io', 'de');
        $this->assertEquals(2, count($result));

        $news = $rootNode->getNode('news');
        $news1 = $rootNode->getNode('news/news-1');
        $test = $rootNode->getNode('test');
        $test1 = $rootNode->getNode('test/news-1');

        // before
        $this->assertTrue($news->getPropertyValue('sulu:history'));
        $this->assertEquals($test, $news->getPropertyValue('sulu:content'));

        $this->assertTrue($news1->getPropertyValue('sulu:history'));
        $this->assertEquals($test1, $news1->getPropertyValue('sulu:content'));

        $this->assertFalse($test->getPropertyValue('sulu:history'));
        $this->assertEquals($this->content2, $test->getPropertyValue('sulu:content'));

        $this->assertFalse($test1->getPropertyValue('sulu:history'));
        $this->assertEquals($this->content1, $test1->getPropertyValue('sulu:content'));

        sleep(1);
        $this->rlpMapper->restoreByPath('/news', 'sulu_io', 'de');

        // after
        $this->assertFalse($news->getPropertyValue('sulu:history'));
        $this->assertEquals($this->content2, $news->getPropertyValue('sulu:content'));

        $this->assertTrue($news1->getPropertyValue('sulu:history'));
        $this->assertEquals($test1, $news1->getPropertyValue('sulu:content'));

        $this->assertTrue($test->getPropertyValue('sulu:history'));
        $this->assertEquals($news, $test->getPropertyValue('sulu:content'));

        $this->assertFalse($test1->getPropertyValue('sulu:history'));
        $this->assertEquals($this->content1, $test1->getPropertyValue('sulu:content'));

        // load history
        $result = $this->rlpMapper->loadHistoryByContentUuid($this->content2->getIdentifier(), 'sulu_io', 'de');

        $this->assertEquals(2, count($result));
        $this->assertEquals('/test', $result[0]->getResourceLocator());
        $this->assertTrue($result[0]->getCreated() > $result[1]->getCreated());
        $this->assertEquals('/asdf', $result[1]->getResourceLocator());
    }
}
