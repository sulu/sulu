<?php

namespace Sulu\Component\Url;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Component\Webspace\Url;

class UrlTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->url = new Url();
    }

    public function testToArray()
    {
        $expected = array(
            'language' => 'ello',
            'country' => 'as',
            'segment' => 'def',
            'redirect' => 'def',
            'url' => 'foo',
        );

        $this->url->setUrl($expected['url']);
        $this->url->setLanguage($expected['language']);
        $this->url->setCountry($expected['country']);
        $this->url->setSegment($expected['segment']);
        $this->url->setRedirect($expected['redirect']);

        $this->assertEquals($expected, $this->url->toArray());
    }
}
