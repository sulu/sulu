<?php

namespace Sulu\Component\Environment;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Component\Webspace\Environment;

class EnvironmentTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->environment = new Environment();
        $this->url = $this->prophesize('Sulu\Component\Webspace\Url');
    }

    public function testToArray()
    {
        $expected = array(
            'type' => 'foo',
            'urls' => array(
                array('asd'),
            ),
        );

        $this->url->toArray()->willReturn(array('asd'));
        $this->environment->addUrl($this->url->reveal());
        $this->environment->setType($expected['type']);


        $this->assertEquals($expected, $this->environment->toArray());
    }
}
