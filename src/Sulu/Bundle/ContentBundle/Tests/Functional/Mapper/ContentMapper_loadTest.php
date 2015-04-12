<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Mapper;

use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\StructureInterface;
use PHPCR\Util\PathHelper;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\ContentBundle\Document\PageDocument;

class ContentMapper_loadTest extends SuluTestCase
{
    private $contentMapper;
    private $inspector;
    private $documentManager;

    public function setUp()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager');
        $this->inspector = $this->getContainer()->get('sulu_document_manager.document_inspector');
    }

    public function provideLoad()
    {
        return array(
            array(
                'en', 'de', false, false, false,
                false,
            ),
            array(
                'en', 'de', true, false, true,
                true,
            ),
        );
    }
    
    /**
     * @dataProvider provideLoad
     *
     * @param mixed $excludeGhost
     * @param mixed $loadGhostContent
     * @param mixed $excludeShadow
     */
    public function testLoadByNode($locale, $requestLocale, $excludeGhost = true, $loadGhostContent = false, $excludeShadow = true, $shouldBeNull)
    {
        $document = $this->createDocument('/cmf/sulu_io/contents/node', $locale);
        $this->documentManager->flush();

        $document = $this->contentMapper->loadByNode(
            $this->inspector->getNode($document),
            $requestLocale,
            'sulu_io',
            $excludeGhost,
            $loadGhostContent,
            $excludeShadow
        );

        if ($shouldBeNull) {
            $this->assertNull($document);
            return;
        }

        $this->assertInstanceOf(PageDocument::class, $document);
    }

    /**
     * Data provider
     */
    public function provideLoadByParent()
    {
        return array(
            array(
                array('child1', 'child2'),
                1, false, false, false,
                array(
                    'child1', 'child2'
                ),
            ),
            array(
                array('child1', 'child2', 'child2/child3'),
                2, true, false, false,
                array(
                    'child1', 'child2', 'child2/child3',
                ),
            ),
            array(
                array('child1', 'child2', 'child2/child3', 'child2/child3/child4'),
                4, true, false, false,
                array(
                    'child1', 'child2', 'child2/child3', 'child2/child3/child4',
                ),
            ),
            array(
                array('child1', 'child2', 'child2/child3'),
                1, true, false, false,
                array(
                    'child1', 'child2',
                ),
            ),
        );
    }

    /**
     * @dataProvider provideLoadByParent
     *
     * Load children for the given parent node
     *
     * @param mixed $children Names of the children to create
     * @param mixed $depth Depth to fetch (or prefetch)
     * @param mixed $flat Return as a flat list (not nested)
     * @param mixed $ignoreExceptions 
     * @param mixed $excludeGhosts
     * @param array $expected
     */
    public function testLoadByParent($children, $depth, $flat, $ignoreExceptions, $excludeGhosts, array $expected)
    {
        $locale = 'de';
        $parent = $this->createDocument('/cmf/sulu_io/contents/parent', $locale);
        foreach ($children as $childPath) {
            $this->createDocument('/cmf/sulu_io/contents/parent/' . $childPath, $locale);
        }
        $this->documentManager->flush();

        $result = $this->contentMapper->loadByParent(
            $parent->getUuid(),
            'sulu_io',
            $locale,
            $depth,
            $flat,
            $ignoreExceptions,
            $excludeGhosts
        );

        $paths = array();
        foreach ($result as $document) {
            $paths[] = substr($document->getPath(), strlen($parent->getPath()) + 1);
        }

        $this->assertEquals($expected, $paths);
    }

    public function testLoadStartPage()
    {
        $startPage = $this->contentMapper->loadStartPage('sulu_io', 'de');
        $this->assertEquals('/cmf/sulu_io/contents', $startPage->getPath());
    }

    public function testLoadByResourceLocator_startPage()
    {
        $content = $this->contentMapper->loadByResourceLocator('/', 'sulu_io', 'de');
        $this->assertEquals('/cmf/sulu_io/contents', $content->getPath());
    }

    public function testLoadByResourceLocator()
    {
        $this->createDocument('/cmf/sulu_io/contents/foo-bar', 'de');
        $this->documentManager->flush();

        $content = $this->contentMapper->loadByResourceLocator('/foo-bar', 'sulu_io', 'de');
        $this->assertEquals('/cmf/sulu_io/contents/foo-bar', $content->getPath());
    }

    public function testLoadBySql2()
    {
        $locale = 'fr';
        $limit = 2;

        $this->createDocument('/cmf/sulu_io/contents/foo-bar', 'fr');
        $this->createDocument('/cmf/sulu_io/contents/foo-bar/bar', 'fr');
        $this->createDocument('/cmf/sulu_io/contents/foo-bar/bar/baz', 'fr');

        $this->documentManager->flush();

        $query = 'SELECT * FROM [nt:unstructured] AS a WHERE ISDESCENDANTNODE("a", "/cmf/sulu_io/contents")';
        $structures = $this->contentMapper->loadBySql2($query, $locale, 'sulu_io', $limit);
        $this->assertCount(2, $structures);
    }

    public function provideLoadByQuery()
    {
        return array(
            // exclude ghost content with two documents, one of which would be a ghost
            array(
                'fr', 
                array(
                    'foo-bar' => 'fr',
                    'foo-bar/foo' => 'de',
                ),
                true, true,
                1
            ),
            // both documents are in the requested locale
            array(
                'fr', 
                array(
                    'foo-bar' => 'fr',
                    'foo-bar/foo' => 'fr',
                ),
                false, true,
                2
            ),
            // documents are in different locales but ghost content is allowed
            array(
                'fr', 
                array(
                    'foo-bar' => 'fr',
                    'foo-bar/foo' => 'de',
                ),
                false, true,
                2
            ),
        );
    }

    /**
     * @dataProvider provideLoadByQuery
     */
    public function testLoadByQuery($requestedLocale, $documents, $excludeGhost, $loadGhostContent, $expectedNbResults)
    {
        foreach ($documents as $name => $locale) {
            $this->createDocument('/cmf/sulu_io/contents/' . $name, $locale);
        }

        $this->documentManager->flush();

        $query = $this->documentManager->createQuery('SELECT * FROM [sulu:page]');
        $result = $this->contentMapper->loadByQuery(
            $query->getPhpcrQuery(),
            $requestedLocale,
            'sulu_io',
            $excludeGhost,
            $loadGhostContent
        );

        $this->assertCount(3, $result);
    }

    public function provideLoadTreeByUuid()
    {
        return array(
            // exclude ghost content with two documents, one of which would be a ghost
            array(
                array(
                    'foo-bar' => 'fr',
                    'foo-boo' => 'fr',
                    'foo-bar/foo' => 'de',
                    'foo-bar/baz' => 'de',
                    'foo-bar/bar' => 'de',
                    'foo-bar/foo/foo' => 'de',
                ),
                'fr', 
                false, true,
                2
            ),
        );
    }

    /**
     * Load tree by UUID should return all the children of the webspace content document
     *
     * @dataProvider provideLoadTreeByUuid
     */
    public function testLoadTreeByUuid($documents, $requestedLocale, $excludeGhost, $loadGhostContent, $expectedNbResults)
    {
        $childDocument = null;
        foreach ($documents as $name => $locale) {
            $document = $this->createDocument('/cmf/sulu_io/contents/' . $name, $locale);
        }

        $this->documentManager->flush();
        $this->documentManager->clear();

        $results = $this->contentMapper->loadTreeByUuid(
            null,
            $requestedLocale, 'sulu_io', $excludeGhost, $loadGhostContent
        );

        $this->assertCount($expectedNbResults, $results);
    }

    /**
     * Now the same operation as load tree by UUID
     *
     * @dataProvider provideLoadTreeByUuid
     */
    public function testLoadTreeByPath($documents, $requestLocale, $excludeGhost, $loadGhostContent, $expectedNbResults)
    {
        $this->testLoadtreeByUuid($documents, $requestLocale, $excludeGhost, $loadGhostContent, $expectedNbResults);
    }

    public function testLoadBreadcrumb()
    {
        $root = $this->documentManager->find('/cmf/sulu_io/contents');
        $descendant1 = $this->createDocument('/cmf/sulu_io/contents/foo-bar', 'fr');
        $descendant2 = $this->createDocument('/cmf/sulu_io/contents/foo-bar/bar', 'fr');
        $descendant3 = $this->createDocument('/cmf/sulu_io/contents/foo-bar/bar/baz', 'fr');

        $expectedCrumbs = array(
            array(
                'depth' => 0,
                'title' => 'Homepage',
                'uuid' => $root->getUuid(),
            ),
            array(
                'depth' => 1,
                'title' => 'foo-bar',
                'uuid' => $descendant1->getUuid(),
            ),
            array(
                'depth' => 2,
                'title' => 'bar',
                'uuid' => $descendant2->getUuid(),
            ),
            array(
                'depth' => 3,
                'title' => 'baz',
                'uuid' => $descendant3->getUuid(),
            ),
        );

        $this->documentManager->flush();
        $this->documentManager->clear();

        $breadcrumb = $this->contentMapper->loadBreadcrumb($descendant3->getUuid(), 'fr', 'sulu_io');

        $crumbs = array();
        foreach ($breadcrumb as $item) {
            $crumbs[] = $item->toArray();
        }

        $this->assertEquals($expectedCrumbs, $crumbs);
    }

    private function createDocument($path, $locale)
    {
        $parent = $this->documentManager->find(PathHelper::getParentPath($path), $locale);
        $name = PathHelper::getNodeName($path);
        $resourceLocator = substr($path, strlen('/cmf/sulu_io/contents'));

        if (null === $parent) {
            throw new \InvalidArgumentException('Cannot find parent: ' . $path);
        }

        $document = new PageDocument();
        $document->setTitle($name);
        $document->setParent($parent);
        $document->setStructureType('contact');
        $document->setResourceSegment($resourceLocator);

        $this->documentManager->persist($document, $locale);

        return $document;
    }
}
