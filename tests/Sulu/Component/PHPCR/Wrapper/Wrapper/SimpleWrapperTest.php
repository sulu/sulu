<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapper;

use Sulu\Component\PHPCR\Wrapper\WrappedObjectInterface;
use Sulu\Component\PHPCR\Wrapper\Traits\NodeTrait;
use PHPCR\NodeInterface;

class SimpleWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->wrapper = new SimpleWrapper(array(
            'PHPCR\NodeInterface' => 'Sulu\Component\PHPCR\Wrapper\Wrapper\TestWrappedNode'
        ));

        $this->phpcrNode = $this->getMock('PHPCR\NodeInterface');
    }

    public function testWrapObject()
    {
        $wrappedObject = $this->wrapper->wrap($this->phpcrNode, 'PHPCR\NodeInterface');
        $this->assertInstanceOf('Sulu\Component\PHPCR\Wrapper\Wrapper\TestWrappedNode', $wrappedObject);
        $this->assertSame($this->phpcrNode, $wrappedObject->getWrappedObject());
    }

    public function testWrapMany()
    {
        $res = $this->wrapper->wrapMany(new \ArrayObject($this->phpcrNode), 'PHPCR\NodeInterface');
        $this->assertInstanceOf('\ArrayObject', $res);
    }
}

class TestWrappedNode implements \IteratorAggregate, WrappedObjectInterface, NodeInterface
{
    use NodeTrait;

    protected $object;

    public function setWrappedObject($object)
    {
        $this->object = $object;
    }

    public function getWrappedObject()
    {
        return $this->object;
    }

    public function getIterator()
    {
        return $this->getNodes();
    }
}
