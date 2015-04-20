<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\StructureInterface;
use PHPCR\PropertyType;
use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;

class ContentMapperSnippetTest extends SuluTestCase
{
    /**
     * @var ContentMapper
     */
    private $contentMapper;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Snippet
     */
    private $snippet1;

    /**
     * @var Snippet
     */
    private $snippet2;

    /**
     * @var string
     */
    private $snippet1OriginalPath;

    public function setUp()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->loadFixtures();
        $this->parent = $this->documentManager->find('/cmf/sulu_io/contents');
    }

    public function loadFixtures()
    {
        $req = ContentMapperRequest::create()
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('animal')
            ->setLocale('en')
            ->setUserId(1)
            ->setData(array(
                'title' => 'ElePHPant',
            ))
            ->setState(StructureInterface::STATE_PUBLISHED);

        $this->snippet1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('animal')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Penguin',
            ))
            ->setState(StructureInterface::STATE_PUBLISHED);

        $this->snippet2 = $this->contentMapper->saveRequest($req);

        $this->snippet1Node = $this->session->getNodeByIdentifier($this->snippet1->getUuid());
        $this->snippet1OriginalPath = $this->snippet1Node->getPath();

        $req = ContentMapperRequest::create()
            ->setUuid($this->snippet1->getUuid())
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('animal')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'English ElePHPant',
            ))
            ->setState(StructureInterface::STATE_PUBLISHED);
        $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('animal')
            ->setLocale('en')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Some other animal',
            ))
            ->setState(StructureInterface::STATE_PUBLISHED);
        $this->contentMapper->saveRequest($req);
    }

    public function testChangeSnippetTemplate()
    {
        $req = ContentMapperRequest::create()
            ->setUuid($this->snippet1->getUuid())
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setUserId(1)
            ->setData(array(
                'title' => 'ElePHPant',
            ));
        $this->contentMapper->saveRequest($req);

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false);
        } catch (\PHPCR\PathNotFoundException $e) {
            $this->assertTrue(true);
        }

        $node = $this->session->getNode('/cmf/snippets/hotel/elephpant');
        $node->getPropertyValue('template');
    }

    public function testRenameSnippet()
    {
        $originalPosition = $this->getNodePosition($this->snippet1Node);

        $req = ContentMapperRequest::create()
            ->setUuid($this->snippet1->getUuid())
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('animal')
            ->setLocale('de')
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setUserId(1)
            ->setData(array(
                'title' => 'ElePHPant FOOBAR',
            ));
        $this->contentMapper->saveRequest($req);
        $node = $this->session->getNode('/cmf/snippets/animal/elephpant');
        $node->getPropertyValue('template');

        $this->assertEquals($originalPosition, $this->getNodePosition($node));
    }

    public function testRemoveSnippet()
    {
        $this->contentMapper->delete($this->snippet1->getUuid(), 'sulu_io');

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false, 'Snippet was found FAIL');
        } catch (\PHPCR\PathNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     */
    public function testRemoveSnippetWithReferences()
    {
        $document = $this->documentManager->create('page');
        $document->setTitle('Hello');
        $document->getContent()->bind(array(
            'animals' => array($this->snippet1->getUuid()),
        ), false);
        $document->setParent($this->parent);
        $document->setStructureType('test_page');
        $document->setResourceSegment('/url/foo');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $this->contentMapper->delete($this->snippet1->getUuid(), 'sulu_io', true);

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false, 'Snippet was found FAIL');
        } catch (\PHPCR\PathNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function provideRemoveSnippetWithReferencesDereference()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * @dataProvider provideRemoveSnippetWithReferencesDereference
     */
    public function testRemoveSnippetWithReferencesDereference($multiple = false)
    {
        $document = $this->documentManager->create('page');
        $document->setTitle('test');
        $document->setResourceSegment('/url/foo');

        if ($multiple) {
            $document->getContent()->bind(array(
                'animals' => array($this->snippet1->getUuid(), $this->snippet2->getUuid()),
            ), false);
        } else {
            $document->getContent()->bind(array(
                'animals' => $this->snippet1->getUuid(),
            ), false);
        }

        $document->setParent($this->parent);
        $document->setStructureType('test_page');
        $this->documentManager->persist($document, 'de');

        $this->documentManager->flush();

        $this->contentMapper->delete($this->snippet1->getUuid(), 'sulu_io', true);

        try {
            $this->session->getNode($this->snippet1OriginalPath);
            $this->assertTrue(false, 'Snippet was found FAIL');
        } catch (\PHPCR\PathNotFoundException $e) {
            $this->assertTrue(true, 'Snippet was removed');
        }

        $referrer = $this->documentManager->find('/cmf/sulu_io/contents/test', 'de');

        if ($multiple) {
            $contents = $referrer->getContent()->getProperty('animals')->getValue();
            $this->assertCount(1, $contents);
            $content = reset($contents);
            $this->assertEquals($this->snippet2->getUuid(), $content);
        } else {
            $contents = $referrer->getContent()->getProperty('animals')->getValue();
            $this->assertCount(0, $contents);
        }
    }

    public function testLoad()
    {
        $node = $this->session->getNode($this->snippet1OriginalPath);
        $snippet = $this->contentMapper->loadByNode(
            $node,
            'de',
            null,
            false,
            true
        );

        $templateKey = $snippet->getKey();
        $this->assertEquals('animal', $templateKey);
    }

    public function testLoadShallowStructureByNode()
    {
        $node = $this->session->getNode($this->snippet1OriginalPath);
        $snippet = $this->contentMapper->loadShallowStructureByNode(
            $node,
            'de',
            'sulu_io'
        );

        $this->assertEquals('animal', $snippet->getKey());
        $this->assertTrue(UUIDHelper::isUUID($snippet->getUuid()));
    }

    /**
     * @expectedException Sulu\Component\DocumentManager\Exception\DocumentNotFoundException
     * @expectedExceptionMessage Requested document of type "page" but got
     *
     */
    public function testUpdatePageWrongType()
    {
        $req = ContentMapperRequest::create()
            ->setUuid($this->snippet1->getUuid())
            ->setType(Structure::TYPE_PAGE)
            ->setWebspaceKey('sulu_io')
            ->setTemplateKey('test_page')
            ->setLocale('de')
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setUserId(1)
            ->setData(array('title' => 'Foo'));

        $this->contentMapper->saveRequest($req);
    }

    /**
     * Return the position of the node within the set of its siblings
     *
     * @return integer
     */
    private function getNodePosition($node)
    {
        $path = $node->getPath();
        $position = null;
        $parent = $this->session->getNode(PathHelper::getParentPath($path));
        $nodes = $parent->getNodes();

        $index = 0;
        foreach ($nodes as $node) {
            if ($node->getPath() === $path) {
                $position = $index;
                break;
            }
            $index++;
        }

        if (null === $position) {
            throw new \RuntimeException(
                sprintf(
                    'Could not find node "%s" as a child of its parent',
                    $path
                )
            );
        }

        return $position;
    }
}
