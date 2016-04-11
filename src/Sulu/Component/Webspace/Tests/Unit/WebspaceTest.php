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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class WebspaceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var Portal
     */
    private $portal;

    /**
     * @var Localization
     */
    private $localization;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var Theme
     */
    private $theme;

    /**
     * @var Segment
     */
    private $segment;

    public function setUp()
    {
        parent::setUp();

        $this->webspace = new Webspace();

        $this->portal = $this->prophesize('Sulu\Component\Webspace\Portal');
        $this->localization = $this->prophesize('Sulu\Component\Localization\Localization');
        $this->security = $this->prophesize('Sulu\Component\Webspace\Security');
        $this->segment = $this->prophesize('Sulu\Component\Webspace\Segment');
        $this->theme = $this->prophesize('Sulu\Component\Webspace\Theme');
    }

    public function testToArray()
    {
        $expected = [
            'key' => 'foo',
            'name' => 'portal_key',
            'localizations' => [
                ['fr'],
            ],
            'security' => [
                'system' => 'sec_sys',
            ],
            'segments' => [
                [
                    'asd',
                ],
            ],
            'portals' => [
                ['one'],
            ],
            'theme' => [
                'dsa',
            ],
            'navigation' => [
                'contexts' => [],
            ],
        ];

        $this->security->getSystem()->willReturn($expected['security']['system']);
        $this->localization->toArray()->willReturn($expected['localizations'][0]);
        $this->segment->toArray()->willReturn($expected['segments'][0]);
        $this->theme->toArray()->willReturn($expected['theme']);
        $this->portal->toArray()->willReturn($expected['portals'][0]);

        $this->webspace->setKey($expected['key']);
        $this->webspace->setName($expected['name']);
        $this->webspace->setLocalizations(
            [
                $this->localization->reveal(),
            ]
        );
        $this->webspace->setSecurity($this->security->reveal());
        $this->webspace->setSegments(
            [
                $this->segment->reveal(),
            ]
        );
        $this->webspace->setPortals(
            [
                $this->portal->reveal(),
            ]
        );
        $this->webspace->setTheme($this->theme->reveal());

        $res = $this->webspace->toArray();
        $this->assertEquals($expected, $res);
    }

    private function getLocalization($language, $country = '', $shadow = null)
    {
        $locale = new Localization();
        $locale->setLanguage($language);
        $locale->setCountry($country);
        $locale->setShadow($shadow);

        return $locale;
    }

    public function testFindLocalization()
    {
        $localeDe = $this->getLocalization('de');
        $localeDeAt = $this->getLocalization('de', 'at');
        $localeDeCh = $this->getLocalization('de', 'ch');

        $localeDe->addChild($localeDeAt);
        $localeDe->addChild($localeDeCh);

        $localeEn = $this->getLocalization('en');

        $this->webspace->addLocalization($localeDe);
        $this->webspace->addLocalization($localeEn);

        $result = $this->webspace->getLocalization('de');
        $this->assertEquals('de', $result->getLocalization());

        $result = $this->webspace->getLocalization('de_at');
        $this->assertEquals('de_at', $result->getLocalization());

        $result = $this->webspace->getLocalization('de_ch');
        $this->assertEquals('de_ch', $result->getLocalization());

        $result = $this->webspace->getLocalization('en');
        $this->assertEquals('en', $result->getLocalization());

        $result = $this->webspace->getLocalization('en_us');
        $this->assertEquals(null, $result);
    }

    public function testHasDomain()
    {
        $environment = $this->prophesize(Environment::class);
        $environment->getUrls()->willReturn([new Url('sulu.lo')]);
        $this->portal->getEnvironment('prod')->willReturn($environment->reveal());
        $this->webspace->addPortal($this->portal->reveal());

        $this->assertTrue($this->webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertFalse($this->webspace->hasDomain('1.sulu.lo', 'prod'));
    }

    public function testHasDomainWildcard()
    {
        $environment = $this->prophesize(Environment::class);
        $environment->getUrls()->willReturn([new Url('{host}')]);
        $this->portal->getEnvironment('prod')->willReturn($environment->reveal());
        $this->webspace->addPortal($this->portal->reveal());

        $this->assertTrue($this->webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertTrue($this->webspace->hasDomain('1.sulu.lo', 'prod'));
    }
}
