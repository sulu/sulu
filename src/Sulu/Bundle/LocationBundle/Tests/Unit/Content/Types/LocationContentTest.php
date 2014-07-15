<?php

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\LocationBundle\Content\Types\LocationContent;

class LocationContentTest extends \PHPUnit_Framework_TestCase
{
    protected $nodeRepository;
    protected $locationContent;

    public function setUp()
    {
        $this->nodeRepository = $this->getMock('Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface');
        $this->phpcrNode = $this->getMock('PHPCR\NodeInterface');
        $this->suluProperty = $this->getMock('Sulu\Component\Content\PropertyInterface');
        $this->locationContent = new LocationContent($this->nodeRepository, 'Foo:bar.html.twig');

    }

    protected function initReadTest($data)
    {
        $this->suluProperty->expects($this->once())
            ->method('setValue')
            ->with($data);
    }

    public function provideRead()
    {
        return array(
            array(
                array('foo_bar' => 'bar_foo'),
            )
        );
    }

    /**
     * @dataProvider provideRead
     */
    public function testRead($data)
    {
        $this->initReadTest($data);

        $this->phpcrNode->expects($this->once())
            ->method('getPropertyValueWithDefault')
            ->with('foobar', '{}')
            ->will($this->returnValue(json_encode($data)));

        $this->suluProperty->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('foobar'));

        $this->locationContent->read(
            $this->phpcrNode,
            $this->suluProperty,
            'webspace_key',
            'fr',
            'segment'
        );
    }

    /**
     * @dataProvider provideRead
     */
    public function testReadForPreview($data)
    {
        $this->initReadTest($data);

        $this->locationContent->readForPreview(
            $data,
            $this->suluProperty,
            'webspace_key',
            'fr',
            'segment'
        );
    }

    /**
     * @dataProvider provideRead
     */
    public function testWrite($data)
    {
        $this->suluProperty->expects($this->once())
            ->method('getName')
            ->willReturn('myname');

        $this->suluProperty->expects($this->once())
            ->method('getValue')
            ->willReturn($data);

        $this->phpcrNode->expects($this->once())
            ->method('setProperty')
            ->with('myname', json_encode($data));

        $this->locationContent->write(
            $this->phpcrNode,
            $this->suluProperty,
            1,
            'webspace_key',
            'fr',
            'segment'
        );
    }
}
