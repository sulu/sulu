<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapped;

use Sulu\Component\PHPCR\Wrapper\Wrapped\Session;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->session = new Session;
        $this->wrapped = $this->getMock('PHPCR\SessionInterface');
        $this->wrapper = $this->getMock('Sulu\Component\PHPCR\Wrapper\WrapperInterface');
        $this->session->setWrappedObject($this->wrapped);
        $this->session->setWrapper($this->wrapper);
    }

    public function provideObjectReturn()
    {
        return array(
            array('wrap', 'getAccessControlManager', 'PHPCR\Security\AccessControlManagerInterface'),
            array('wrap', 'getItem', 'PHPCR\ItemInterface', array('/')),
            array('wrap', 'getNode', 'PHPCR\NodeInterface', array('/')),
            array('wrap', 'getNodeByIdentifier', 'PHPCR\NodeInterface', array('/asd')),
            array('wrapMany', 'getNodes', 'PHPCR\NodeInterface', array(array())),
            array('wrapMany', 'getNodesByIdentifier', 'PHPCR\NodeInterface', array(array())),
            array('wrapMany', 'getProperties', 'PHPCR\PropertyInterface', array('/')),
            array('wrap', 'getProperty', 'PHPCR\PropertyInterface', array('/')),
            array('wrap', 'getRepository', 'PHPCR\RepositoryInterface'),
            array('wrap', 'getRetentionManager', 'PHPCR\Retention\RetentionManagerInterface'),
            array('wrap', 'getRootNode', 'PHPCR\NodeInterface')
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

        $refl = new \ReflectionClass(get_class($this->session));
        $method = $refl->getMethod($method);
        $method->invokeArgs($this->session, $args);
    }

    public function provideUnwrappedMethod()
    {
        return array(
            array('checkPermission', array('asd', 'asd')),
            array('exportDocumentView', array('asd', 'asd', 'asd', 'asd')),
            array('exportSystemView', array('asd', 'asd', 'asd', 'asd')),
            array('getAttribute', array('asd')),
            array('getAttributeNames', array('asd', 'asd')),
            array('getNamespacePrefix', array('asd', 'asd')),
            array('getNamespacePrefixes', array('asd', 'asd')),
            array('getNamespaceURI', array('asd', 'asd')),
            array('getUserID'),
            array('getWorkspace', array('asd')),
            array('hasCapability', array('asd', 'ar', array())),
            array('hasPendingChanges'),
            array('hasPermission', array('perm', 'asd')),
            array('importXML', array('/', 'asd', 'nasd')),
            array('isLive'),
            array('itemExists', array('/')),
            array('logout'),
            array('move', array('/', '/')),
            array('nodeExists', array('/')),
            array('propertyExists', array('/prop')),
            array('refresh', array(true)),
            array('removeItem', array('prop')),
            array('save'),
            array('setNamespacePrefix', array('foobar', 'barfoo')),
        );
    }

    /**
     * @dataProvider provideUnwrappedMethod
     */
    public function testUnwrappedMethod($method, $args = array())
    {
        $this->wrapped->expects($this->once())
            ->method($method);

        $refl = new \ReflectionClass(get_class($this->session));
        $method = $refl->getMethod($method);
        $method->invokeArgs($this->session, $args);
    }
}
