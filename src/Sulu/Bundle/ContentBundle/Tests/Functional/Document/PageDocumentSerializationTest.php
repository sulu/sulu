<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Integration\Document;

use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;

class PageDocumentSerializationTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var object
     */
    private $parent;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    public function setUp()
    {
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->initPhpcr();
        $this->parent = $this->documentManager->find('/cmf/sulu_io/contents', 'de');
        $this->serializer = $this->getContainer()->get('jms_serializer');
        $this->registry = $this->getContainer()->get('sulu_document_manager.document_registry');
    }

    /**
     * It can serialize content that contains objects.
     *
     * NOTE: We do not persist so that we can use any type
     *       of content - persisting would cause the content
     *       to be validated.
     */
    public function testSerialization()
    {
        $internalLink = $this->createPage([
            'title' => 'Hello',
        ]);

        $page = $this->createPage([
            'title' => 'Foobar',
            'object' => $internalLink,
            'arrayOfObjects' => [
                $internalLink,
                $internalLink,
            ],
            'integer' => 1234,
        ]);

        $result = $this->serializer->serialize($page, 'json');

        return $result;
    }

    /**
     * It can deserialize content that contains objects.
     *
     * @depends testSerialization
     */
    public function testDeserialization($data)
    {
        $page = $this->serializer->deserialize($data, PageDocument::class, 'json');

        $this->assertInstanceOf(PageDocument::class, $page);
        $this->assertEquals('Foobar', $page->getTitle());
        $content = $page->getStructure();

        $this->assertInternalType('integer', $content->getProperty('integer')->getValue());

        $this->assertInstanceOf(Structure::class, $content);
        $this->assertCount(2, $content->getProperty('arrayOfObjects')->getValue());
    }

    /**
     * It can serialize persisted documents.
     */
    public function testSerializationPersisted()
    {
        $page = $this->createPage([
            'title' => 'Hello',
        ]);
        $this->documentManager->persist($page, 'de');
        $this->documentManager->flush();

        $result = $this->serializer->serialize($page, 'json');

        return $result;
    }

    /**
     * It can deserialize persisted documents with routes.
     */
    public function testDeserializationPersisted()
    {
        $page = $this->createPage([
            'title' => 'Hello',
        ]);
        $this->documentManager->persist($page, 'de');
        $this->documentManager->flush();

        $result = $this->serializer->serialize($page, 'json');

        $page = $this->serializer->deserialize($result, PageDocument::class, 'json');

        $this->assertInstanceOf(PageDocument::class, $page);
        $this->assertEquals('Hello', $page->getStructure()->getProperty('title')->getValue());
        $this->assertEquals('de', $this->registry->getOriginalLocaleForDocument($page));
    }

    private function createPage($data)
    {
        $page = new PageDocument();

        $uuidReflection = new \ReflectionProperty(PageDocument::class, 'uuid');
        $uuidReflection->setAccessible(true);
        $uuidReflection->setValue($page, 1);

        $page->setTitle($data['title']);
        $page->setParent($this->parent);
        $page->setStructureType('contact');
        $page->setResourceSegment('/foo');
        $page->getStructure()->bind($data, true);

        return $page;
    }
}
