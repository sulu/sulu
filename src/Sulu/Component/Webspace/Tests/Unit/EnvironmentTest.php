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

use Prophecy\Argument;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Url;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $expected = [
            'type' => 'foo',
            'urls' => [
                ['test'],
            ],
        ];

        $environment = new Environment();

        $environment->addUrl($this->getUrl(['test']));
        $environment->setType($expected['type']);

        $this->assertEquals($expected, $environment->toArray());
    }

    public function addUrlProvider()
    {
        $urls = [
            // case 0
            $this->getUrl([], false),
            // case 1
            $this->getUrl([], true),
            // case 2
            $this->getUrl([], true),
            $this->getUrl([], false),
            // case 3
            $this->getUrl([], false),
            $this->getUrl([], true),
            // case 4
            $this->getUrl([], false),
            $this->getUrl([], true),
            $this->getUrl([], false),
        ];

        return [
            [[$urls[0]], $urls[0]],
            [[$urls[1]], $urls[1]],
            [[$urls[2], $urls[3]], $urls[2]],
            [[$urls[4], $urls[5]], $urls[5]],
            [[$urls[6], $urls[7], $urls[8]], $urls[7]],
        ];
    }

    /**
     * @dataProvider addUrlProvider
     */
    public function testAddUrl(array $urls, Url $expectedMainUrl)
    {
        $environment = new Environment();
        foreach ($urls as $url) {
            $environment->addUrl($url);
        }

        $this->assertEquals($urls, $environment->getUrls());
        $this->assertEquals($expectedMainUrl, $environment->getMainUrl());

        foreach ($environment->getUrls() as $url) {
            $this->assertEquals(($url === $environment->getMainUrl()), $url->isMain());
        }
    }

    /**
     * Return url with given to-array result.
     *
     * @param array $toArrayResult
     * @param bool $isMain
     *
     * @return Url
     */
    private function getUrl($toArrayResult, $isMain = false)
    {
        $url = $this->prophesize(Url::class);
        $url->isMain()->willReturn($isMain);
        $url->setMain(Argument::any())->will(
            function ($args) use ($url) {
                $url->isMain()->willReturn($args[0]);
            }
        );
        $url->toArray()->willReturn($toArrayResult);
        $url->setEnvironment(Argument::any())->willReturn(true);

        return $url->reveal();
    }
}
