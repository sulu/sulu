<?php

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Functional;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\Query\ResultCollection;
use Sulu\Bundle\DocumentManagerBundle\Tests\Document\PageDocument;

class DocumentManagerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
    }

    /**
     * It should return an undefined document for an unmanaged node
     */
    public function testFind()
    {
        $document = $this->documentManager->find('/cmf', 'fr');
        $this->assertInstanceOf(UnknownDocument::class, $document);
    }

    /**
     * It should return a Page document for a managed page document
     */
    public function testFindPage()
    {
        $document = $this->documentManager->find('/cmf/sulu_io/contents', 'fr');
        $this->assertInstanceOf(PageDocument::class, $document);

        return $document;
    }

    /**
     * It should have a parent document
     */
    public function testTraverseParent()
    {
        $document = $this->documentManager->find('/cmf/sulu_io/contents', 'fr');
        $this->assertInstanceOf(PageDocument::class, $document);
        $parent = $document->getParent();
        $this->assertInstanceOf(UnknownDocument::class, $parent);
        $this->assertEquals('sulu_io', $parent->getNodeName());

        $parent = $parent->getParent();
        $this->assertEquals('cmf', $parent->getNodeName());
    }


    /**
     * It should pesist a new document
     */
    public function testPersistPage()
    {
        $page = $this->createPage('', 'Hello World');
        $this->documentManager->persist($page, 'fr');
        $this->documentManager->flush();
        $this->documentManager->clear();

        $document = $this->documentManager->find('/cmf/sulu_io/contents/hello-world', 'fr');

        $this->assertInstanceOf(PageDocument::class, $document);
    }

    /**
     * It should return the same instance when asking for the same document twice
     */
    public function testFindMultipleSameInstance()
    {
        $document1 = $this->documentManager->find('/cmf/sulu_io/contents', 'fr');
        $document2 = $this->documentManager->find('/cmf/sulu_io/contents', 'fr');

        $this->assertSame($document1, $document2);
    }

    /**
     * I should be able to query
     */
    public function testQuery()
    {
        $page1 = $this->createPage('', 'Hello');
        $page2 = $this->createPage('/hello', 'World');
        $this->documentManager->flush();

        $query = $this->documentManager->createQuery(
            'SELECT * FROM [sulu:page]'
        );
        $results = $query->execute();

        $this->assertInstanceOf(ResultCollection::class, $results);
        $results = $results->toArray();

        foreach ($this->dispatcher->getCalledListeners() as $listener) {
        }
        $this->assertCount(3, $results);
        $this->assertContains($page1, $results);
        $this->assertContains($page2, $results);
    }

    private function createPage($relPath = '', $title)
    {
        $parentDocument = $this->documentManager->find('/cmf/sulu_io/contents' . $relPath, 'fr');
        $page = new PageDocument();
        $page->setTitle($title);
        $page->setParent($parentDocument);
        $this->documentManager->persist($page, 'fr');

        return $page;
    }
}
