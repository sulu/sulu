<?php

namespace Sulu\Component\PHPCR\Model;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;

class NodeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->phpcrNode = $this->getMockForAbstractClass('Sulu\Component\PHPCR\Model\TestNode');
        $this->contentContext = $this->getMock('Sulu\Component\Content\ContentContextInterface');
        $this->wrapper = $this->getMock('Sulu\Component\PHPCR\Wrapper\WrapperInterface');
        $this->property = $this->getMockForAbstractClass('Sulu\Component\PHPCR\Model\TestProperty');
        $this->node = new Node();
        $this->node->setWrapper($this->wrapper);
        $this->node->setContentContext($this->contentContext);
        $this->node->setWrappedObject($this->phpcrNode);

        $this->contentContext->expects($this->any())
            ->method('getLanguageNamespace')
            ->will($this->returnValue('lang'));
    }

    public function testGetTranslatedProperty()
    {
        $this->phpcrNode->expects($this->once())
            ->method('getProperty')
            ->with('lang:de-foo')
            ->will($this->returnValue($this->property));
        $this->wrapper->expects($this->once())
            ->method('wrap')
            ->with($this->property)
            ->will($this->returnValue($this->property));

        $res = $this->node->getTranslatedProperty('foo');

        $this->assertSame($this->property, $res);
    }

    public function testGetTranslatedPropertyWithDefault()
    {
        $this->phpcrNode->expects($this->once())
            ->method('getPropertyValueWithDefault')
            ->with('lang:de-foo', 'bar')
            ->will($this->returnValue('foo'));

        $res = $this->node->getTranslatedPropertyValue('foo', 'bar');
        $this->assertEquals('foo', $res);
    }
}

abstract class TestNode implements \IteratorAggregate, NodeInterface
{
    public function getIterator()
    {
        return array();
    }
}

abstract class TestProperty implements \IteratorAggregate, PropertyInterface
{
    public function getIterator()
    {
        return array();
    }
}
