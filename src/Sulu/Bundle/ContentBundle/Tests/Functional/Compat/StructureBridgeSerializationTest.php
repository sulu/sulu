<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Compat;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\StructureInterface;
use PHPCR\Util\NodeHelper;
use DTL\Bundle\ContentBundle\Tests\Integration\BaseTestCase;
use DTL\Component\Content\Compat\Structure\StructureBridge;
use DTL\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Property as LegacyProperty;

class StructureBridgeSerializationTest extends SuluTestCase
{
    private $serializer;
    private $contentDocument;
    private $contentMapper;

    public function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager');

        $this->contentDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
        $this->serializer = $this->getContainer()->get('jms_serializer');
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
    }

    public function testSerialize()
    {
        $pageDocument = $this->createPage();
        $startPage = $this->contentMapper->load($pageDocument->getUuid(), 'sulu_io', 'en');
        $this->assertInstanceOf(StructureBridge::class, $startPage);

        $result = $this->serializer->serialize($startPage, 'json');

        return $result;
    }

    /**
     * @depends testSerialize
     */
    public function testDeserialize($data)
    {
        $result = $this->serializer->deserialize($data, StructureBridge::class, 'json');

        $this->assertInstanceOf(StructureBridge::class, $result);
        $this->assertEquals('internal_links', $result->getKey());

        $property = $result->getProperty('internal_links');
        $this->assertInstanceOf(LegacyProperty::class, $property);

        $value = $property->getValue();
        $this->assertInternalType('array', $value);
        $this->assertCount(2, $value);
        $this->assertContainsOnlyInstancesOf(StructureBridge::class, $value);
        $first = reset($value);

        $this->assertEquals($this->contentDocument->getPath(), $first->getPath());
    }

    private function createPage()
    {
        $page = new PageDocument();
        $page->setTitle('Hello');
        $page->setResourceSegment('hello');
        $page->setLocale('fr');
        $page->setParent($this->contentDocument);
        $page->setStructureType('internal_links');
        $page->getContent()->bind(array(
            'title' => 'World',
            'internal_links' => array(
                $this->contentDocument->getUuid(),
                $this->contentDocument->getUuid(),
            ),
        ));

        $this->getDm()->persist($page);
        $this->getDm()->flush();

        return $page;
    }
}
