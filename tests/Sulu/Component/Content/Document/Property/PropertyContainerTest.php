<?php

namespace Sulu\Component\Content\Document\Property;

use Sulu\Component\Content\Document\Property\PropertyContainer;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Document\Property\PropertyInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Content\Structure\Property;
use Prophecy\Argument;
use Sulu\Component\Content\Type\ContentTypeInterface;
use Sulu\Component\DocumentManager\Behavior\LocaleBehavior;

class PropertyContainerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->structure = $this->prophesize(Structure::class);
        $this->document = $this->prophesize(LocaleBehavior::class);;
        $this->contentType = $this->prophesize(ContentTypeInterface::class);
        $this->encoder = $this->prophesize(PropertyEncoder::class);
        $this->structureProperty = $this->prophesize(Property::class);

        $this->propertyContainer = new PropertyContainer(
            $this->contentTypeManager->reveal(),
            $this->node->reveal(),
            $this->encoder->reveal(),
            $this->structure->reveal(),
            $this->document->reveal()
        );
    }

    /**
     * It shuld lazily initialize a localized property
     */
    public function testGetProperty()
    {
        $name = 'test';
        $contentTypeName = 'hello';
        $phpcrName = 'phpcrName';
        $locale = 'fr';

        $this->document->getLocale()->willReturn($locale);
        $this->structureProperty->isLocalized()->willReturn(true);
        $this->structureProperty->getType()->willReturn($contentTypeName);
        $this->structure->getProperty($name)->willReturn($this->structureProperty);
        $this->encoder->localizedContentName($name, $locale)->willReturn($phpcrName);
        $this->contentTypeManager->get($contentTypeName)->willReturn($this->contentType->reveal());
        $this->contentType->read(
            $this->node->reveal(),
            Argument::type('Sulu\Component\Content\Document\Property\PropertyInterface'),
            null,
            null
        )->shouldBeCalledTimes(1);

        $property = $this->propertyContainer->getProperty($name);

        $this->assertInstanceOf(PropertyInterface::class, $property);
        $this->assertEquals($phpcrName, $property->getName());
    }
}
