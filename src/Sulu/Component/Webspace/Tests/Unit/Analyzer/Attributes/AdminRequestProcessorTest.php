<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\AdminRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class AdminRequestProcessorTest extends TestCase
{
    use ProphecyTrait;

    public static function provideData()
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

    #[\PHPUnit\Framework\Attributes\DataProvider('provideData')]
    public function testProcess(array $expected = [], $webspaceKey = null, $locale = null, $language = null): void
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $provider = new AdminRequestProcessor($webspaceManager->reveal(), 'prod');

        $query = \http_build_query([
            'webspace' => $webspaceKey,
            'locale' => $locale,
            'language' => $language,
        ]);
        $request = Request::create('/admin/api?' . $query, 'GET', [], [], [], ['HTTP_HOST' => 'sulu.io']);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn($webspaceKey);

        $expectedLocale = $locale ?: $language;

        $localization = null;
        if ($expectedLocale) {
            $localization = Localization::createFromString($expectedLocale);
            $webspace->getLocalization($expectedLocale)->willReturn($localization);
        }
        $webspaceManager->findWebspaceByKey($webspaceKey)->willReturn($webspaceKey ? $webspace->reveal() : null);

        $result = $provider->process($request, new RequestAttributes());

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $result->getAttribute($key));
        }

        $this->assertEquals($webspaceKey ? $webspace->reveal() : null, $result->getAttribute('webspace'));
        $this->assertEquals($localization, $result->getAttribute('localization'));
    }

    public function testProcessWithWebspaceRouteAttribute(): void
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $provider = new AdminRequestProcessor($webspaceManager->reveal(), 'prod');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getLocalization('de')->willReturn(Localization::createFromString('de'));
        $webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace->reveal());

        $request = Request::create('/admin/api/webspaces/sulu_io/analytics?locale=de', 'GET', [], [], [], ['HTTP_HOST' => 'sulu.io']);
        $request->attributes->set('webspace', 'sulu_io');

        $result = $provider->process($request, new RequestAttributes());

        $this->assertSame('sulu_io', $result->getAttribute('webspaceKey'));
        $this->assertSame('de', $result->getAttribute('locale'));
    }

    public function testProcessWithWebspaceKeyQueryParameter(): void
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $provider = new AdminRequestProcessor($webspaceManager->reveal(), 'prod');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getLocalization('de')->willReturn(Localization::createFromString('de'));
        $webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace->reveal());

        $request = Request::create('/admin/preview/render?webspaceKey=sulu_io&locale=de', 'GET', [], [], [], ['HTTP_HOST' => 'sulu.io']);

        $result = $provider->process($request, new RequestAttributes());

        $this->assertSame('sulu_io', $result->getAttribute('webspaceKey'));
        $this->assertSame('de', $result->getAttribute('locale'));
    }

    public function testValidate(): void
    {
        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $provider = new AdminRequestProcessor($webspaceManager->reveal(), 'prod');

        $this->assertTrue($provider->validate(new RequestAttributes()));
    }
}
