<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapper;

use Sulu\Component\Content\ContentContextAwareInterface;
use Sulu\Component\PHPCR\Wrapper\WrappedObjectInterface;

class SuluWrapperTest extends \PHPUnit_Framework_TestCase
{
    protected $suluWrapper;
    protected $contentContext;
    protected $wrapperObject;
    protected $wrapperObjectClass;

    public function setUp()
    {
        $this->contentContext = $this->getMock('Sulu\Component\Content\ContentContextInterface');
        $this->object = $this->getMock('Sulu\Component\PHPCR\Wrapper\Wrapper\TestFoobar');
        $this->wrapperObject = $this->getMockForAbstractClass('Sulu\Component\PHPCR\Wrapper\Wrapper\TestWrapperObject');
        $this->wrapperObjectClass = get_class($this->wrapperObject);

        $this->suluWrapper = new SuluWrapper(array(
            'Sulu\Component\PHPCR\Wrapper\Wrapper\TestFoobar' => $this->wrapperObjectClass,
        ));
        $this->suluWrapper->setContentContext($this->contentContext);
    }

    public function testSuluWrapper()
    {
        $this->wrapperObject->expects($this->once())
            ->method('setContentContext')
            ->with($this->contentContext);

        $obj = $this->suluWrapper->wrap($this->wrapperObject, 'stdClass');
        $this->assertInstanceOf($this->wrapperObjectClass, $obj);
    }
}

interface TestWrapperObject extends WrappedObjectInterface, ContentContextAwareInterface
{
}

interface Foobar {
}
