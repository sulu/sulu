<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Sulu\Component\Webspace\Url;

class UrlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Url
     */
    private $url;

    public function setUp()
    {
        parent::setUp();

        $this->url = new Url();
    }

    public function testToArray()
    {
        $expected = [
            'language' => 'ello',
            'country' => 'as',
            'segment' => 'def',
            'redirect' => 'def',
            'url' => 'foo',
            'main' => true,
            'analyticsKey' => 'analytics',
            'environment' => null,
        ];

        $this->url->setUrl($expected['url']);
        $this->url->setLanguage($expected['language']);
        $this->url->setCountry($expected['country']);
        $this->url->setSegment($expected['segment']);
        $this->url->setRedirect($expected['redirect']);
        $this->url->setMain($expected['main']);
        $this->url->setAnalyticsKey($expected['analyticsKey']);

        $this->assertEquals($expected, $this->url->toArray());
    }
}
