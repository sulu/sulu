<?php

namespace Sulu\Component\Content\Document;

use Sulu\Component\Content\Document\ContentInstanceFactory;
use Sulu\Component\Content\Document\Property\PropertyContainerInterface;
use DTL\DecoratorGenerator\DecoratorFactory;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;

class ContentInstanceFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->content = $this->prophesize(ContentBehavior::class);
        $this->decoratorFactory = $this->prophesize(DecoratorFactory::class);

        $this->factory = new ContentInstanceFactory($this->decoratorFactory->reveal());
    }

    /**
     * It should dynamically create a class instance for the given structure type name
     * and content
     */
    public function testGetInstance()
    {
        $this->content->getStructureType()->willReturn('foobar');
        $this->decoratorFactory->decorate(
            $this->content->reveal(),
            ContentInstanceFactory::getTargetClassName(get_class($this->content->reveal()), 'foobar')
        )->willReturn(new \stdClass);
        $instance = $this->factory->getInstance($this->content->reveal(), 'foobar');
        $this->assertInstanceOf('stdClass', $instance);

    }

    /**
     * It should return the structure type for a generated class
     */
    public function testGetStructureType()
    {
        $className = ContentInstanceFactory::getTargetClassName(get_class($this->content->reveal()), 'foobar');
        $structureType = ContentInstanceFactory::getStructureType($className);
        $this->assertEquals('foobar', $structureType);
    }

    /**
     * It should throw an exception for a non-wrapped class
     *
     * @expectedException RuntimeException
     */
    public function testGetStructureTypeNonWrapped()
    {
        $this->factory->getStructureType('stdClass');
    }
}
