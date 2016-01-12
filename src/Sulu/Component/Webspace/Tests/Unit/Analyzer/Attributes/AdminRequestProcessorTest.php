<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\AdminRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class AdminRequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function provideData()
    {
        return [
            [],
            [['webspaceKey' => 'sulu_io'], 'sulu_io'],
            [['webspaceKey' => 'sulu_io', 'locale' => 'de'], 'sulu_io', 'de'],
            [['webspaceKey' => 'sulu_io', 'locale' => 'de_at'], 'sulu_io', null, 'de_at'],
            [['webspaceKey' => 'sulu_io', 'locale' => 'de'], 'sulu_io', 'de', 'de_at'],
            [['locale' => 'de'], null, 'de'],
            [['locale' => 'de_at'], null, null, 'de_at'],
            [['locale' => 'de'], null, 'de', 'de_at'],
        ];
    }

    /**
     * @dataProvider provideData
     */
    public function testProcess(array $expected = [], $webspaceKey = null, $locale = null, $language = null)
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $provider = new AdminRequestProcessor($webspaceManager->reveal(), 'prod');

        $request = $this->prophesize(Request::class);
        $request->get('webspace')->willReturn($webspaceKey);
        $request->get('locale', $language)->willReturn($locale ?: $language);
        $request->get('language')->willReturn($language);

        $localization = $this->prophesize(Localization::class);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn($webspaceKey);
        if ($locale || $language) {
            $webspace->getLocalization($locale ?: $language)->willReturn($localization->reveal());
        }
        $webspaceManager->findWebspaceByKey($webspaceKey)->willReturn($webspaceKey ? $webspace->reveal() : null);

        $result = $provider->process($request->reveal(), new RequestAttributes());

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $result->getAttribute($key));
        }

        $this->assertEquals($webspaceKey ? $webspace->reveal() : null, $result->getAttribute('webspace'));
        $this->assertEquals(
            $webspaceKey && ($locale || $language) ? $localization->reveal() : null,
            $result->getAttribute('localization')
        );
    }

    public function testValidate()
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $provider = new AdminRequestProcessor($webspaceManager->reveal(), 'prod');

        $this->assertTrue($provider->validate(new RequestAttributes()));
    }
}
