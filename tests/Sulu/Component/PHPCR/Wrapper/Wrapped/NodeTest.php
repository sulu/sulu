<?php

namespace Sulu\Component\PHPCR\Wrapped\Wrapper;

use Sulu\Component\PHPCR\Wrapper\Wrapped\Node;

class NodeTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->node = new Node;
        $this->wrapped = $this->getMock('PHPCR\NodeInterface');
        $this->wrapper = $this->getMock('Sulu\Component\PHPCR\Wrapper\WrapperInterface');
        $this->node->setWrappedObject($this->wrapped);
        $this->node->setWrapper($this->wrapper);
    }

    public function provideObjectReturn()
    {
        return array(
            array('wrap', 'addNode', 'PHPCR\NodeInterface', array('asd')),
            array('wrap', 'getDefinition', 'PHPCR\NodeType\NodeDefinitionInterface'),
            array('wrapMany', 'getMixinNodeTypes', 'PHPCR\NodeType\NodeTypeInterface'),
            array('wrap', 'getNode', 'PHPCR\NodeInterface', array('/foo')),
            array('wrapMany', 'getNodes', 'PHPCR\NodeInterface'),
            array('wrap', 'getPrimaryItem', 'PHPCR\ItemInterface'),
            array('wrap', 'getPrimaryNodeType', 'PHPCR\NodeType\NodeTypeInterface'),
            array('wrapMany', 'getProperties', 'PHPCR\PropertyInterface'),
            array('wrap', 'getProperty', 'PHPCR\PropertyInterface', array('/prop')),
            array('wrapMany', 'getReferences', 'PHPCR\PropertyInterface'),
            array('wrapMany', 'getSharedSet', 'PHPCR\NodeInterface'),
            array('wrap', 'getParent', 'PHPCR\NodeInterface'),
            array('wrap', 'getSession', 'PHPCR\SessionInterface'),
        );
    }

    /**
     * @dataProvider provideObjectReturn
     */
    public function testObjectReturn($wrapMethod, $method, $expectedClass, $args = array())
    {
        $this->wrapped->expects($this->once())
            ->method($method);

        $this->wrapper->expects($this->once())
            ->method($wrapMethod)
            ->with(null, $expectedClass);

        $refl = new \ReflectionClass(get_class($this->node));
        $method = $refl->getMethod($method);
        $method->invokeArgs($this->node, $args);
    }

    public function provideUnwrappedMethod()
    {
        return array(
            array('addMixin', array('nt:mixin')),
            array('addNodeAutoNamed', array()),
            array('canAddMixin', array('mix')),
            array('followLifecycleTransition', array('asd')),
            array('getAllowedLifecycleTransitions'),
            array('getCorrespondingNodePath', array('workspace')),
            array('getIdentifier'),
            array('getIndex'),
            array('getNodeNames'),
            array('getPrimaryNodeType'),
            array('getPropertiesValues'),
            array('getPropertyValue', array('asd')),
            array('getPropertyValueWithDefault', array('asd', 'asd')),
            array('getWeakReferences'),
            array('hasNode', array('asd')),
            array('hasNodes', array()),
            array('hasProperties', array()),
            array('hasProperty', array('asd')),
            array('isCheckedOut', array()),
            array('isLocked', array()),
            array('isNodeType', array('ads')),
            array('orderBefore', array('asd', 'asd')),
            array('removeMixin', array('asd')),
            array('removeShare', array()),
            array('removeSharedSet', array()),
            array('rename', array('asd')),
            array('setMixins', array(array('asd', 'asd'))),
            array('setPrimaryType', array('asd')),
            array('setProperty', array('asd', 'asd')),
            array('update', array(true)),
        );
    }

    /**
     * @dataProvider provideUnwrappedMethod
     */
    public function testUnwrappedMethod($method, $args = array())
    {
        $this->wrapped->expects($this->once())
            ->method($method);

        $refl = new \ReflectionClass(get_class($this->node));
        $method = $refl->getMethod($method);
        $method->invokeArgs($this->node, $args);
    }

    public function testIterator()
    {
        $this->wrapped->expects($this->once())
            ->method('getNodes')
            ->will($this->returnValue(array(
                'asd'
            )));
        $this->wrapper->expects($this->once())
            ->method('wrapMany')
            ->will($this->returnValue(array(
                'asd'
            )));

        $this->node->getNodes();
    }

    public function testPreservingDefaultValueInWrapped()
    {
    }
}
