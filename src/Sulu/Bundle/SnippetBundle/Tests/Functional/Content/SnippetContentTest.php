<?php

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Content;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Component\Content\StructureInterface;
use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;

class SnippetContentTypeTest extends BaseFunctionalTestCase
{
    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();

        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->property = $this->getMock('Sulu\Component\Content\PropertyInterface');

        $this->contentType = new SnippetContent($this->contentMapper);
    }

    public function testPropertyRead()
    {
        $me = $this;

        $this->property->expects($this->once())
            ->method('getName')->will($this->returnValue('i18n:de-hotels'));
        $this->property->expects($this->once())
            ->method('setValue')
            ->will($this->returnCallback(function ($snippets) use ($me) {
                foreach ($snippets as $snippet) {
                    $me->assertInstanceOf('HotelSnippetCache', $snippet);
                }
            }));

        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels-page');
        $this->contentType->read($pageNode, $this->property, 'sulu_io', 'de', null);
    }

    public function testPropertyWrite()
    {
        // property should have been written by the content mapper
        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels-page');
        $this->assertTrue($pageNode->hasProperty('i18n:de-hotels'));
        $prop = $pageNode->getProperty('i18n:de-hotels');
        $values = $prop->getValue();
        $this->assertCount(2, $values);
        $hotel1 = reset($values);
        $this->assertEquals('Le grande budapest', $hotel1->getPropertyValue('i18n:de-title'));
    }

    public function testGetContentData()
    {
        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels-page');
        $pageStructure = $this->contentMapper->loadByNode($pageNode, 'de', 'sulu_io');
        $property = $pageStructure->getProperty('hotels');
        $data = $this->contentType->getContentData($property, 'sulu_io', 'de', null);
        $this->assertCount(2, $data);
        $hotel1 = reset($data);
        $this->assertEquals('Le grande budapest', $hotel1['title']);
        $hotel2 = next($data);
        $this->assertEquals('L\'Hôtel New Hampshire', $hotel2['title']);
    }

    public function testRemove()
    {
        $this->property->expects($this->any())
            ->method('getName')->will($this->returnValue('i18n:de-hotels'));

        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels-page');
        $this->contentType->remove($pageNode, $this->property, 'sulu_io', 'de', null);
        $this->session->save();
        $this->assertFalse($pageNode->hasProperty('i18n:de-hotels'));
    }
}
