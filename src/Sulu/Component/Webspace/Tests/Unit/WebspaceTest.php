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
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Segment;
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
     * @var string
     */
    private $theme = 'test';

    /**
     * @var Segment
     */
    private $segment;

    public function setUp()
    {
        parent::setUp();

        $this->webspace = new Webspace();

        $this->portal = $this->prophesize(Portal::class);
        $this->localization = $this->prophesize(Localization::class);
        $this->security = $this->prophesize(Security::class);
        $this->segment = $this->prophesize(Segment::class);
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
            'templates' => [],
            'defaultTemplates' => [],
            'portals' => [
                ['one'],
            ],
            'theme' => 'test',
            'navigation' => [
                'contexts' => [],
            ],
            'resourceLocator' => [
                'strategy' => 'tree_leaf_edit',
            ],
        ];

        $this->security->getSystem()->willReturn($expected['security']['system']);
        $this->localization->toArray()->willReturn($expected['localizations'][0]);
        $this->segment->toArray()->willReturn($expected['segments'][0]);
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
        $this->webspace->setTheme($this->theme);
        $this->webspace->setResourceLocatorStrategy($expected['resourceLocator']['strategy']);

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

    public function testHasDomainWithLocalization()
    {
        $environment = $this->prophesize(Environment::class);
        $url = new Url('sulu.lo', 'prod');
        $url->setLanguage('de');
        $environment->getUrls()->willReturn([$url]);
        $this->portal->getEnvironment('prod')->willReturn($environment->reveal());
        $this->webspace->addPortal($this->portal->reveal());

        $this->assertTrue($this->webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertTrue($this->webspace->hasDomain('sulu.lo', 'prod', 'de'));
        $this->assertFalse($this->webspace->hasDomain('sulu.lo', 'prod', 'en'));
    }

    public function testHasDomainWithLocalizationWithCountry()
    {
        $environment = $this->prophesize(Environment::class);
        $url = new Url('sulu.lo', 'prod');
        $url->setLanguage('de');
        $url->setCountry('at');
        $environment->getUrls()->willReturn([$url]);
        $this->portal->getEnvironment('prod')->willReturn($environment->reveal());
        $this->webspace->addPortal($this->portal->reveal());

        $this->assertTrue($this->webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertTrue($this->webspace->hasDomain('sulu.lo', 'prod', 'de_at'));
        $this->assertFalse($this->webspace->hasDomain('sulu.lo', 'prod', 'de'));
    }

    public function testAddTemplate()
    {
        $templates = ['error-404' => 'template404'];
        $webspace = new Webspace();
        $webspace->addTemplate('error-404', 'template404');

        $this->assertEquals('template404', $webspace->getTemplate('error-404'));
        $this->assertEquals($templates, $webspace->getTemplates());
        $data = $webspace->toArray();
        $this->assertEquals($templates, $data['templates']);
    }

    public function testAddTemplateDefault()
    {
        $templates = ['error-404' => 'template404', 'error' => 'template'];

        $webspace = new Webspace();
        $webspace->addTemplate('error', 'template');
        $webspace->addTemplate('error-404', 'template404');

        $this->assertEquals('template404', $webspace->getTemplate('error-404'));
        $this->assertEquals('template', $webspace->getTemplate('error'));
        $this->assertEquals($templates, $webspace->getTemplates());
        $data = $webspace->toArray();
        $this->assertEquals($templates, $data['templates']);
    }

    public function testAddDefaultTemplate()
    {
        $defaultTemplates = ['page' => 'default', 'homepage' => 'overview'];

        $webspace = new Webspace();
        $webspace->addDefaultTemplate('page', 'default');
        $webspace->addDefaultTemplate('homepage', 'overview');
        $this->assertEquals($defaultTemplates, $webspace->getDefaultTemplates());
        $this->assertEquals($defaultTemplates['page'], $webspace->getDefaultTemplate('page'));
        $this->assertEquals($defaultTemplates['homepage'], $webspace->getDefaultTemplate('homepage'));
        $this->assertNull($webspace->getDefaultTemplate('other-type'));
        $data = $webspace->toArray();
        $this->assertEquals($defaultTemplates, $data['defaultTemplates']);
    }
}
