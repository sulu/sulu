<?php

namespace Sulu\Component\PHPCR\Wrapper\Traits;

use Sulu\Component\PHPCR\Wrapper\WrappedObjectInterface;
use Sulu\Component\PHPCR\Wrapper\Traits\WrappedObjectTrait;;

class WrappedObjectTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultValue()
    {
        $wrapperObject = new TestWrappedObjectTraitClass;
        $wrappedObject = new TestWrappedObjectSubject();
        $wrapperObject->setWrappedObject($wrappedObject);

        $wrapperObject->thisIsAMethod('Hello');
        $this->assertEquals('FOOBAR', $wrapperObject->getWrappedObject()->z);
    }
}

class TestWrappedObjectTraitClass implements WrappedObjectInterface
{
    use WrappedObjectTrait;

    protected $wrappedObject;


    public function getWrappedObject() 
    {
        return $this->wrappedObject;
    }
    
    public function setWrappedObject($wrappedObject)
    {
        $this->wrappedObject = $wrappedObject;
    }
    

    public function thisIsAMethod($x, $z = null)
    {
        return $this->call('thisIsAMethod', func_get_args());
    }
}

class TestWrappedObjectSubject
{
    public $x;
    public $z;

    public function thisIsAMethod($x, $z = 'FOOBAR')
    {
        $this->x = $x;
        $this->z = $z;
    }
}
