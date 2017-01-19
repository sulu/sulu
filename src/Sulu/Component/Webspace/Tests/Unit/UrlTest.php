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
    public function provideIsValidLocale()
    {
        return [
            ['de', 'at', 'de', 'at', true],
            ['de', '', 'de', null, true],
            ['de', null, 'de', null, true],
            [null, null, 'en', 'gb', true],
            ['en', null, 'de', null, false],
            ['en', 'us', 'en', 'gb', false],
            ['de', 'at', 'de', null, false],
        ];
    }

    /**
     * @dataProvider provideIsValidLocale
     */
    public function testIsValidLocale($urlLanguage, $urlCountry, $testLanguage, $testCountry, $result)
    {
        $url = new Url();
        $url->setLanguage($urlLanguage);
        $url->setCountry($urlCountry);

        $this->assertEquals($result, $url->isValidLocale($testLanguage, $testCountry));
    }

    public function testToArray()
    {
        $url = new Url();

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

        $url->setUrl($expected['url']);
        $url->setLanguage($expected['language']);
        $url->setCountry($expected['country']);
        $url->setSegment($expected['segment']);
        $url->setRedirect($expected['redirect']);
        $url->setMain($expected['main']);
        $url->setAnalyticsKey($expected['analyticsKey']);

        $this->assertEquals($expected, $url->toArray());
    }
}
