<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Content;

use InvalidArgumentException;
use PHPCR\SessionInterface;
use PHPCR\Util\UUIDHelper;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;

class SnippetContentTest extends BaseFunctionalTestCase
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var PropertyInterface
     */
    protected $property;

    /**
     * @var StructureResolverInterface
     */
    protected $structureResolver;

    /**
     * @var ContentTypeInterface
     */
    protected $contentType;

    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();

        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->property = $this->getMock('Sulu\Component\Content\PropertyInterface');

        $this->structureResolver = $this->getContainer()->get('sulu_website.resolver.structure');
        $this->contentType = new SnippetContent(
            $this->contentMapper,
            $this->structureResolver,
            'SomeTemplate.html.twig',
            'somedefault'
        );
    }

    public function testPropertyRead()
    {
        $me = $this;

        $this->property->expects($this->once())
            ->method('getName')->will($this->returnValue('i18n:de-hotels'));
        $this->property->expects($this->once())
            ->method('setValue')
            ->will($this->returnCallback(function ($snippets) use ($me) {
                foreach ($snippets['ids'] as $snippet) {
                    $me->assertTrue(UUIDHelper::isUUID($snippet));
                }
            }));

        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels-page');
        $this->contentType->read($pageNode, $this->property, 'sulu_io', 'de', null);
    }

    public function testPropertyWriteContentMapper()
    {
        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels-page');
        $this->assertTrue($pageNode->hasProperty('i18n:de-hotels'));

        $prop = $pageNode->getProperty('i18n:de-hotels');
        $values = $prop->getValue();

        $this->assertCount(2, $values);

        $hotel1 = reset($values);
        $this->assertEquals('Le grande budapest', $hotel1->getPropertyValue('i18n:de-title'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Property value must either be a UUID or a Snippet
     */
    public function testPropertyWriteUnknownType()
    {
        $this->property->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(array('ids' => 'this-aint-nuffin')));

        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels-page');
        $this->contentType->write($pageNode, $this->property, 0, 'sulu_io', 'de', null);
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
