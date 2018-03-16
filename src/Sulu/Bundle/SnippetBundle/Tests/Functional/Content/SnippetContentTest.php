<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Content;

use PHPCR\SessionInterface;
use PHPCR\Util\UUIDHelper;
use Prophecy\Argument;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

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
     * @var ReferenceStoreInterface
     */
    protected $referenceStore;

    /**
     * @var SnippetContent
     */
    protected $contentType;

    /**
     * @var DefaultSnippetManagerInterface
     */
    protected $defaultSnippetManager;

    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();

        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->property = $this->getMock('Sulu\Component\Content\Compat\PropertyInterface');

        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);

        $this->structureResolver = $this->getContainer()->get('sulu_website.resolver.structure');
        $this->referenceStore = $this->getContainer()->get('sulu_snippet.reference_store.snippet');
        $this->contentType = new SnippetContent(
            $this->defaultSnippetManager->reveal(),
            $this->getContainer()->get('sulu_snippet.resolver'),
            $this->referenceStore,
            true,
            'SomeTemplate.html.twig'
        );

        $this->getContainer()->get('sulu_document_manager.document_manager')->clear();
    }

    public function testPropertyRead()
    {
        $this->property->expects($this->exactly(2))
            ->method('getName')->will($this->returnValue('i18n:de-hotels'));
        $this->property->expects($this->once())
            ->method('setValue')
            ->will($this->returnCallback(function ($snippets) {
                foreach ($snippets as $snippet) {
                    $this->assertTrue(UUIDHelper::isUUID($snippet));
                }
            }));

        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels');
        $this->contentType->read($pageNode, $this->property, 'sulu_io', 'de', null);
    }

    public function testPropertyWriteContentMapper()
    {
        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels');
        $this->assertTrue($pageNode->hasProperty('i18n:de-hotels'));

        $prop = $pageNode->getProperty('i18n:de-hotels');
        $values = $prop->getValue();

        $this->assertCount(2, $values);

        $hotel1 = reset($values);
        $this->assertEquals('Le grande budapest', $hotel1->getPropertyValue('i18n:de-title'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Property value must either be a UUID or a Snippet
     */
    public function testPropertyWriteUnknownType()
    {
        $this->property->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(['ids' => 'this-aint-nuffin']));

        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels');
        $this->contentType->write($pageNode, $this->property, 0, 'sulu_io', 'de', null);
    }

    public function testGetContentData()
    {
        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels');
        $pageStructure = $this->contentMapper->loadByNode($pageNode, 'de');
        $property = $pageStructure->getProperty('hotels');
        $data = $this->contentType->getContentData($property);
        $this->assertCount(2, $data);
        $hotel1 = reset($data);
        $this->assertEquals('Le grande budapest', $hotel1['title']);
        $hotel2 = next($data);
        $this->assertEquals('L\'Hôtel New Hampshire', $hotel2['title']);
    }

    public function testGetContentDataShadow()
    {
        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels');
        $pageStructure = $this->contentMapper->loadByNode($pageNode, 'en', 'sulu_io', true, false, false);
        $property = $pageStructure->getProperty('hotels');

        $data = $this->contentType->getContentData($property);

        $this->assertCount(2, $data);
        $hotel1 = reset($data);
        $this->assertEquals('Le grande budapest (en)', $hotel1['title']);
        $hotel2 = next($data);
        $this->assertEquals('L\'Hôtel New Hampshire', $hotel2['title']);
    }

    public function testPreResolve()
    {
        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels');
        $pageStructure = $this->contentMapper->loadByNode($pageNode, 'en', 'sulu_io', true, false, false);
        $property = $pageStructure->getProperty('hotels');
        $this->contentType->preResolve($property);

        $uuids = $this->referenceStore->getAll();
        $this->assertCount(2, $uuids);
        foreach ($uuids as $uuid) {
            $this->assertTrue(UUIDHelper::isUuid($uuid));
        }
    }

    public function testRemove()
    {
        $this->property->expects($this->any())
            ->method('getName')->will($this->returnValue('i18n:de-hotels'));

        $pageNode = $this->session->getNode('/cmf/sulu_io/contents/hotels');
        $this->contentType->remove($pageNode, $this->property, 'sulu_io', 'de', null);
        $this->session->save();
        $this->assertFalse($pageNode->hasProperty('i18n:de-hotels'));
    }

    public function testGetContentDataDefaultNoType()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getIsShadow()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getParams()->willReturn([]);

        $this->defaultSnippetManager->load(Argument::cetera())->shouldNotBeCalled();

        $data = $this->contentType->getContentData($property->reveal());
        $this->assertCount(0, $data);
    }

    public function testGetContentDataDefaultNoDefault()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getIsShadow()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getParams()->willReturn(
            [
                'snippetType' => new PropertyParameter('snippetType', 'test'),
                'default' => new PropertyParameter('default', true),
            ]
        );

        $this->defaultSnippetManager->load('sulu_io', 'test', 'de_at')
            ->shouldBeCalledTimes(1)
            ->willReturn(null);

        $data = $this->contentType->getContentData($property->reveal());
        $this->assertCount(0, $data);
    }

    public function testGetContentDataDefault()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getIsShadow()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getParams()->willReturn(
            [
                'snippetType' => new PropertyParameter('snippetType', 'test'),
                'default' => new PropertyParameter('default', true),
            ]
        );

        $this->defaultSnippetManager->load('sulu_io', 'test', 'de_at')
            ->shouldBeCalledTimes(1)
            ->willReturn($this->hotel1);

        $data = $this->contentType->getContentData($property->reveal());
        $this->assertCount(1, $data);
    }

    public function testGetContentDataDefaultWrongType()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getIsShadow()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getParams()->willReturn(
            [
                'snippetType' => new PropertyParameter('snippetType', 'test'),
                'default' => new PropertyParameter('default', true),
            ]
        );

        $this->defaultSnippetManager->load('sulu_io', 'test', 'de_at')
            ->willThrow(new WrongSnippetTypeException('', '', $this->hotel1));

        $data = $this->contentType->getContentData($property->reveal());
        $this->assertCount(0, $data);
    }

    public function testGetContentDataDefaultZone()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getIsShadow()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getParams()->willReturn(
            [
                'snippetType' => new PropertyParameter('snippetType', 'test'),
                'default' => new PropertyParameter('default', 'sidebar.homepage'),
            ]
        );

        $this->defaultSnippetManager->load('sulu_io', 'sidebar.homepage', 'de_at')
            ->shouldBeCalledTimes(1)
            ->willReturn($this->hotel1);

        $data = $this->contentType->getContentData($property->reveal());
        $this->assertCount(1, $data);
    }

    public function testGetContentDataDefaultDefaultNotSet()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getIsShadow()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getParams()->willReturn(
            [
                'snippetType' => new PropertyParameter('snippetType', 'test'),
            ]
        );

        $this->defaultSnippetManager->load(Argument::cetera())->shouldNotBeCalled();

        $data = $this->contentType->getContentData($property->reveal());
        $this->assertCount(0, $data);
    }

    public function testGetContentDataDefaultDefaultFalse()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getWebspaceKey()->willReturn('sulu_io');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getIsShadow()->willReturn(false);

        $property = $this->prophesize(PropertyInterface::class);
        $property->getValue()->willReturn([]);
        $property->getStructure()->willReturn($structure->reveal());
        $property->getParams()->willReturn(
            [
                'snippetType' => new PropertyParameter('snippetType', 'test'),
                'default' => new PropertyParameter('default', false),
            ]
        );

        $this->defaultSnippetManager->load(Argument::cetera())->shouldNotBeCalled();

        $data = $this->contentType->getContentData($property->reveal());
        $this->assertCount(0, $data);
    }
}
