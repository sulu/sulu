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

use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class TreeStrategyTest extends SuluTestCase
{
    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

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

    public function setUp()
    {
        $this->rlpStrategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->documentInspector = $this->getContainer()->get('sulu_document_manager.document_inspector');
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');

        $this->initPhpcr();
    }

    public function testDeleteByPath()
    {
        $rootNode = $this->session->getNode('/cmf/sulu_io/routes/de');

        $parentDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        // create routes for content
        $news = $this->createDocument($parentDocument, 'news', '/news');
        $this->documentManager->persist($news, 'de');
        $this->documentManager->flush();

        $news1 = $this->createDocument($news, 'news-1', '/news/news-1');
        $this->documentManager->persist($news1, 'de');
        $this->documentManager->flush();

        $sub1 = $this->createDocument($news1, 'sub-1', '/news/news-1/sub-1');
        $this->documentManager->persist($sub1, 'de');
        $this->documentManager->flush();

        $sub2 = $this->createDocument($news1, 'sub-1', '/news/news-1/sub-2');
        $this->documentManager->persist($sub2, 'de');
        $this->documentManager->flush();

        // move route
        $news = $this->documentManager->find($news->getUuid(), 'de');
        $news->setResourceSegment('/test');
        $this->rlpStrategy->save($news, null);
        $this->session->save();
        $this->session->refresh(false);

        // delete a history url
        $this->rlpStrategy->deleteByPath('/news/news-1/sub-1', 'sulu_io', 'de');
        $this->assertFalse($rootNode->hasNode('news/news-1/sub-1'));
        $this->assertTrue($rootNode->hasNode('news/news-1/sub-2'));
        $this->assertTrue($rootNode->hasNode('test/news-1/sub-1'));
        $this->assertTrue($rootNode->hasNode('test/news-1/sub-2'));

        // delete a normal url
        $this->rlpStrategy->deleteByPath('/test/news-1/sub-2', 'sulu_io', 'de');
        $this->assertFalse($rootNode->hasNode('news/news-1/sub-1'));
        $this->assertFalse($rootNode->hasNode('news/news-1/sub-2'));

        $this->assertTrue($rootNode->hasNode('news/news-1'));
        $this->assertTrue($rootNode->hasNode('test/news-1/sub-1'));
        $this->assertFalse($rootNode->hasNode('test/news-1/sub-2'));
    }

    public function testMoveTree()
    {
        // create routes for content
        $parentDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $news = $this->createDocument($parentDocument, 'news', '/news');
        $this->documentManager->persist($news, 'de');
        $this->documentManager->flush();

        $news1 = $this->createDocument($news, 'news-1', '/news/news-1');
        $this->documentManager->persist($news1, 'de');
        $this->documentManager->flush();

        $news1sub1 = $this->createDocument($news1, 'sub-1', '/news/news-1/sub-1');
        $this->documentManager->persist($news1sub1, 'de');
        $this->documentManager->flush();

        $news1sub2 = $this->createDocument($news1, 'sub-2', '/news/news-1/sub-2');
        $this->documentManager->persist($news1sub2, 'de');
        $this->documentManager->flush();

        $news2 = $this->createDocument($news, 'news-2', '/news/news-2');
        $this->documentManager->persist($news2, 'de');
        $this->documentManager->flush();

        $news2sub1 = $this->createDocument($news2, 'news-2', '/news/news-2/sub-1');
        $this->documentManager->persist($news2sub1, 'de');
        $this->documentManager->flush();

        $news2sub2 = $this->createDocument($news2, 'news-2', '/news/news-2/sub-2');
        $this->documentManager->persist($news2sub2, 'de');
        $this->documentManager->flush();

        // move route
        $news = $this->documentManager->find($news->getUuid(), 'de');
        $news->setResourceSegment('/test');
        $this->rlpStrategy->save($news, null);
        $this->session->save();
        $this->session->refresh(false);

        // check exist new routes
        $this->assertEquals(
            $news->getUuid(),
            $this->rlpStrategy->loadByResourceLocator('/test', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $news1->getUuid(),
            $this->rlpStrategy->loadByResourceLocator('/test/news-1', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $news1sub1->getUuid(),
            $this->rlpStrategy->loadByResourceLocator('/test/news-1/sub-1', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $news1sub2->getUuid(),
            $this->rlpStrategy->loadByResourceLocator('/test/news-1/sub-2', 'sulu_io', 'de')
        );

        $this->assertEquals(
            $news2->getUuid(),
            $this->rlpStrategy->loadByResourceLocator('/test/news-2', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $news2sub1->getUuid(),
            $this->rlpStrategy->loadByResourceLocator('/test/news-2/sub-1', 'sulu_io', 'de')
        );
        $this->assertEquals(
            $news2sub2->getUuid(),
            $this->rlpStrategy->loadByResourceLocator('/test/news-2/sub-2', 'sulu_io', 'de')
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

    public function testRestore()
    {
        $rootNode = $this->session->getNode('/cmf/sulu_io/routes/de');
        $parentDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        // create routes for content
        $newsDocument = $this->createDocument($parentDocument, 'news', '/news');
        $this->documentManager->persist($newsDocument, 'de');
        $this->documentManager->flush();

        $news1Document = $this->createDocument($newsDocument, 'news-1', '/news/news-1');
        $this->documentManager->persist($news1Document, 'de');
        $this->documentManager->flush();
        $this->session->refresh(false);

        // move route
        $newsDocument = $this->documentManager->find($newsDocument->getUuid(), 'de');
        $newsDocument->setResourceSegment('/asdf');
        $this->rlpStrategy->save($newsDocument, null);
        $this->session->save();
        $this->session->refresh(false);

        $newsDocument->setResourceSegment('/test');
        $this->rlpStrategy->save($newsDocument, null);
        $this->session->save();
        $this->session->refresh(false);

        // load history
        $result = $this->rlpStrategy->loadHistoryByContentUuid($newsDocument->getUuid(), 'sulu_io', 'de');
        $this->assertEquals(2, count($result));

        $news = $rootNode->getNode('news');
        $asdf = $rootNode->getNode('asdf');
        $test = $rootNode->getNode('test');
        $news1 = $rootNode->getNode('news/news-1');
        $test1 = $rootNode->getNode('test/news-1');

        // before
        $this->assertTrue($news->getPropertyValue('sulu:history'));
        $this->assertEquals($test, $news->getPropertyValue('sulu:content'));

        $this->assertTrue($asdf->getPropertyValue('sulu:history'));
        $this->assertEquals($test, $asdf->getPropertyValue('sulu:content'));

        $this->assertFalse($test->getPropertyValue('sulu:history'));
        $this->assertEquals(
            $this->documentInspector->getNode($newsDocument),
            $test->getPropertyValue('sulu:content')
        );

        $this->assertTrue($news1->getPropertyValue('sulu:history'));
        $this->assertEquals($test1, $news1->getPropertyValue('sulu:content'));

        $this->assertFalse($test1->getPropertyValue('sulu:history'));
        $this->assertEquals(
            $this->documentInspector->getNode($news1Document),
            $test1->getPropertyValue('sulu:content')
        );

        sleep(1);
        $this->rlpStrategy->restoreByPath('/news', 'sulu_io', 'de');

        // after
        $this->assertFalse($news->getPropertyValue('sulu:history'));
        $this->assertEquals(
            $this->documentInspector->getNode($newsDocument),
            $news->getPropertyValue('sulu:content')
        );

        $this->assertTrue($news1->getPropertyValue('sulu:history'));
        $this->assertEquals($test1, $news1->getPropertyValue('sulu:content'));

        $this->assertTrue($test->getPropertyValue('sulu:history'));
        $this->assertEquals($news, $test->getPropertyValue('sulu:content'));

        $this->assertFalse($test1->getPropertyValue('sulu:history'));
        $this->assertEquals(
            $this->documentInspector->getNode($news1Document),
            $test1->getPropertyValue('sulu:content')
        );

        // load history
        $result = $this->rlpStrategy->loadHistoryByContentUuid($newsDocument->getUuid(), 'sulu_io', 'de');

        $this->assertEquals(2, count($result));
        $this->assertEquals('/test', $result[0]->getResourceLocator());
        $this->assertTrue($result[0]->getCreated() > $result[1]->getCreated());
        $this->assertEquals('/asdf', $result[1]->getResourceLocator());
    }

    private function getRlForHistory($rl)
    {
        try {
            $this->rlpStrategy->loadByResourceLocator($rl, 'sulu_io', 'de');

            return false;
        } catch (ResourceLocatorMovedException $ex) {
            return $ex->getNewResourceLocator();
        }
    }

    private function createDocument($parentDocument, $title, $url)
    {
        $document = $this->documentManager->create('page');
        $document->setTitle($title);
        $document->setParent($parentDocument);
        $document->setStructureType('default');
        $document->setResourceSegment($url);

        // FIXME required because search indexing will fail otherwise
        $document->setExtensionsData(
            ['excerpt' => ['title' => '', 'description' => '', 'categories' => [], 'tags' => []]]
        );

        return $document;
    }
}
