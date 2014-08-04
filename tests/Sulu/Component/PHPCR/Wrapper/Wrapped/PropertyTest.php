<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapped;

use Sulu\Component\PHPCR\Wrapper\Wrapped\Property;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->node = new Property;
        $this->wrapped = $this->getMock('PHPCR\PropertyInterface');
        $this->wrapper = $this->getMock('Sulu\Component\PHPCR\Wrapper\WrapperInterface');
        $this->node->setWrappedObject($this->wrapped);
        $this->node->setWrapper($this->wrapper);
    }

    public function provideObjectReturn()
    {
        return array(
            array('wrap', 'getDefinition', 'PHPCR\PropertyDefinitionInterface'),
            array('wrap', 'getNode', 'PHPCR\NodeInterface'),
            array('wrap', 'getProperty', 'PHPCR\PropertyInterface'),
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
            array('addValue', array('asd')),
            array('getBinary', array()),
            array('getBoolean', array()),
            array('getDate', array()),
            array('getDecimal', array()),
            array('getDouble', array()),
            array('getLength', array()),
            array('getLong', array()),
            array('getString', array()),
            array('getType', array()),
            array('getValue', array()),
            array('isMultiple', array()),
            array('setValue', array('asd')),
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
}
