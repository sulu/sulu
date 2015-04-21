<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Compat;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\StructureInterface;
use PHPCR\Util\NodeHelper;
use Sulu\Bundle\ContentBundle\Tests\Integration\BaseTestCase;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Property;

class StructureBridgeSerializationTest extends SuluTestCase
{
    private $serializer;
    private $contentDocument;
    private $contentMapper;

    public function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->contentDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
        $this->serializer = $this->getContainer()->get('jms_serializer');
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
    }

    public function testSerialize()
    {
        $pageDocument = $this->createPage();
        $managedPage = $this->contentMapper->load($pageDocument->getUuid(), 'sulu_io', 'en');
        $this->assertInstanceOf(StructureBridge::class, $managedPage);

        $result = $this->serializer->serialize($managedPage, 'json');

        return $result;
    }

    /**
     * @depends testSerialize
     */
    public function testDeserialize($data)
    {
        $result = $this->serializer->deserialize($data, PageBridge::class, 'json');

        $this->assertInstanceOf(StructureBridge::class, $result);
        $this->assertEquals('internallinks', $result->getKey());

        $property = $result->getProperty('internalLinks');
        $this->assertInstanceOf(Property::class, $property);

        $value = $property->getValue();
        $this->assertInternalType('array', $value);
        $this->assertCount(1, $value);
    }

    private function createPage()
    {
        $page = new PageDocument();
        $page->setTitle('Hello');
        $page->setResourceSegment('/hello');
        $page->setParent($this->contentDocument);
        $page->setStructureType('internallinks');
        $page->getContent()->bind(array(
            'title' => 'World',
            'internalLinks' => array(
                $this->contentDocument->getUuid(),
            ),
        ));

        $this->documentManager->persist($page, 'fr');
        $this->documentManager->flush();

        return $page;
    }
}
