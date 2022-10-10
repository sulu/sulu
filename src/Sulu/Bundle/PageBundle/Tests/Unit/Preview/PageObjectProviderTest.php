<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Preview;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Preview\PageObjectProvider;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class PageObjectProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<SerializerInterface>
     */
    private $serializer;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var PageObjectProvider
     */
    private $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);

        $this->provider = new PageObjectProvider(
            $this->documentManager->reveal(),
            $this->serializer->reveal(),
            $this->documentInspector->reveal()
        );
    }

    public function testGetObject(string $id = '123-123-123', string $locale = 'de'): void
    {
        $this->documentManager->find($id, $locale)
            ->willReturn($this->prophesize(BasePageDocument::class)->reveal())
            ->shouldBeCalledTimes(1);

        $this->assertInstanceOf(BasePageDocument::class, $this->provider->getObject($id, $locale));
    }

    public function testGetId(string $id = '123-123-123'): void
    {
        $object = $this->prophesize(BasePageDocument::class);
        $object->getUuid()->willReturn($id);

        $this->assertEquals($id, $this->provider->getId($object->reveal()));
    }

    /**
     * @param string[] $data
     */
    public function testSetValues(string $locale = 'de', array $data = ['title' => 'SULU']): void
    {
        $structure = new Structure();
        $object = $this->prophesize(BasePageDocument::class);
        $object->getStructure()->willReturn($structure);

        $this->provider->setValues($object->reveal(), $locale, $data);

        $this->assertEquals('SULU', $structure->getProperty('title')->getValue());
    }

    /**
     * @param string[] $context
     */
    public function testSetContext(string $locale = 'de', array $context = ['template' => 'test-template']): void
    {
        $object = $this->prophesize(BasePageDocument::class);
        $object->setStructureType('test-template')->shouldBeCalled();

        $this->assertEquals($object->reveal(), $this->provider->setContext($object->reveal(), $locale, $context));
    }

    public function testSerialize(): void
    {
        $object = $this->prophesize(BasePageDocument::class);

        $this->serializer->serialize(
            $object->reveal(),
            'json',
            Argument::that(function(SerializationContext $context) {
                return $context->shouldSerializeNull() && $context->getAttribute('groups') === ['preview'];
            })
        )->shouldBeCalled()->willReturn('{"title": "test"}');

        $this->assertEquals('{"title": "test"}', $this->provider->serialize($object->reveal()));
    }

    public function testDeserialize(): void
    {
        $object = $this->prophesize(BasePageDocument::class);

        $this->serializer->deserialize(
            '{"title": "test"}',
            \get_class($object->reveal()),
            'json',
            Argument::that(function(DeserializationContext $context) {
                return $context->getAttribute('groups') === ['preview'];
            })
        )->shouldBeCalled()->willReturn($object->reveal());

        $this->assertEquals(
            $object->reveal(),
            $this->provider->deserialize('{"title": "test"}', \get_class($object->reveal()))
        );
    }

    public function testGetSecurityContext(string $id = '123-123-123', string $locale = 'de'): void
    {
        $page = $this->prophesize(BasePageDocument::class)->reveal();
        $this->documentManager->find($id, $locale)
            ->willReturn($page)
            ->shouldBeCalledTimes(1);

        $this->documentInspector->getWebspace($page)->willReturn('example');

        $this->assertEquals('sulu.webspaces.example', $this->provider->getSecurityContext($id, $locale));
    }
}
