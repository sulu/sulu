<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Integration\Document;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Form\FormInterface;
use Sulu\Component\Content\Form\ContentView;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Symfony\Cmf\Component\RoutingAuto\UriContextCollection;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Component\Content\Document\DocumentInterface;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\PhpcrOdm\ContentContainer;
use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Bundle\ContentBundle\Document\Route;
use Sulu\Component\Content\Document\Property\PropertyContainer;

class PageDocumentSerializationTest extends SuluTestCase
{
    public function setUp()
    {
        $this->manager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->initPhpcr();
        $this->parent = $this->manager->find('/cmf/sulu_io/contents', 'de');
        $this->serializer = $this->getContainer()->get('jms_serializer');
    }

    /**
     * It can serialize content that contains objects
     * 
     * NOTE: We do not persist so that we can use any type
     *       of content - persisting would cause the content
     *       to be validated.
     */
    public function testSerialization()
    {
        $internalLink = new PageDocument();
        $internalLink->setTitle('Hello');

        $page = $this->createPage(array(
            'title' => 'Foobar',
            'object' => $internalLink,
            'arrayOfObjects' => array(
                $internalLink,
                $internalLink,
            ),
            'integer' => 1234,
            'double' => 1234.00,
        ));

        $result = $this->serializer->serialize($page, 'json');

        return $result;
    }

    /**
     * It can deserialize content that contains objects
     *
     * @depends testSerialization
     */
    public function testDeserialization($data)
    {
        $page = $this->serializer->deserialize($data, PageDocument::class, 'json');

        $this->assertInstanceOf(PageDocument::class, $page);
        $this->assertEquals('/foo', $page->getResourceSegment()); $this->assertEquals('Hello', $page->getTitle());
        $content = $page->getContent();

        $this->assertInternalType('double', $content->getProperty('double')->getValue());
        $this->assertInternalType('integer', $content->getProperty('integer')->getValue());
        $this->assertInstanceOf(PageDocument::class, $content->getProperty('object')->getValue());

        $this->assertInstanceOf(PropertyContainer::class, $content);
        $this->assertCount(2, $content->getProperty('arrayOfObjects')->getValue());
        $this->assertContainsOnlyInstancesOf(PageDocument::class, $content->getProperty('arrayOfObjects')->getValue());
    }

    /**
     * It can serialize persisted documents
     */
    public function testSerializationPersisted()
    {
        $page = $this->createPage(array(
            'title' => 'Hello',
        ));
        $this->manager->persist($page, 'de');
        $this->manager->flush();

        $result = $this->serializer->serialize($page, 'json');

        return $result;
    }

    /**
     * It can deserialize persisted documents with routes
     *
     * @depends testSerializationPersisted
     */
    public function testDeserializationPersisted($data)
    {
        $page = $this->serializer->deserialize($data, PageDocument::class, 'json');

        $this->assertInstanceOf(PageDocument::class, $page);
        $this->assertEquals('Hello', $page->getContent()->getProperty('title')->getValue());
    }

    private function createPage($data)
    {
        $page = new PageDocument();
        $page->setTitle('Hello');
        $page->setParent($this->parent);
        $page->setStructureType('contact');
        $page->setResourceSegment('/foo');
        $page->getContent()->bind($data, true);

        return $page;
    }
}
