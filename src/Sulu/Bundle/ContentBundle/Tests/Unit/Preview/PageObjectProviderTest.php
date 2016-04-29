<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Preview;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\ContentBundle\Preview\PageObjectProvider;
use Sulu\Component\Content\Document\Structure\Structure;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class PageObjectProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var PageObjectProvider
     */
    private $provider;

    public function setUp()
    {
        parent::setUp();

        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);

        $this->provider = new PageObjectProvider($this->documentManager->reveal(), $this->serializer->reveal());
    }

    public function testGetObject($id = '123-123-123', $locale = 'de')
    {
        $this->documentManager->find($id, $locale)
            ->willReturn($this->prophesize(BasePageDocument::class)->reveal())->shouldBeCalledTimes(1);

        $this->assertInstanceOf(BasePageDocument::class, $this->provider->getObject($id, $locale));
    }

    public function testGetId($id = '123-123-123')
    {
        $object = $this->prophesize(BasePageDocument::class);
        $object->getUuid()->willReturn($id);

        $this->assertEquals($id, $this->provider->getId($object->reveal()));
    }

    public function testSetValues($locale = 'de', $data = ['title' => 'SULU'])
    {
        $structure = new Structure();
        $object = $this->prophesize(BasePageDocument::class);
        $object->getStructure()->willReturn($structure);

        $this->provider->setValues($object->reveal(), $locale, $data);

        $this->assertEquals('SULU', $structure->getProperty('title')->getValue());
    }

    public function testSetContext($locale = 'de', $context = ['template' => 'test-template'])
    {
        $object = $this->prophesize(BasePageDocument::class);
        $object->setStructureType('test-template')->shouldBeCalled();

        $this->assertEquals($object->reveal(), $this->provider->setContext($object->reveal(), $locale, $context));
    }

    public function testSerialize()
    {
        $object = $this->prophesize(BasePageDocument::class);

        $this->serializer->serialize(
            $object->reveal(),
            'json',
            Argument::that(
                function (SerializationContext $context) {
                    return $context->shouldSerializeNull()
                           && $context->attributes->get('groups')->get() === ['preview'];
                }
            )
        )->shouldBeCalled()->willReturn('{"title": "test"}');

        $this->assertEquals('{"title": "test"}', $this->provider->serialize($object->reveal()));
    }

    public function testDeserialize()
    {
        $object = $this->prophesize(BasePageDocument::class);

        $this->serializer->deserialize(
            '{"title": "test"}',
            get_class($object->reveal()),
            'json',
            Argument::that(
                function (DeserializationContext $context) {
                    return $context->shouldSerializeNull()
                           && $context->attributes->get('groups')->get() === ['preview'];
                }
            )
        )->shouldBeCalled()->willReturn($object->reveal());

        $this->assertEquals(
            $object->reveal(),
            $this->provider->deserialize('{"title": "test"}', get_class($object->reveal()))
        );
    }
}
