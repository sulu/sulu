<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Locale;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Locale\DefaultLocaleProviderInterface;
use Sulu\Bundle\WebsiteBundle\Locale\RequestDefaultLocaleProvider;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestDefaultLocaleProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<Portal>
     */
    private $portal;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var DefaultLocaleProviderInterface
     */
    private $defaultLocaleProvider;

    public function setUp(): void
    {
        parent::setUp();

        $this->portal = $this->prophesize(Portal::class);
        $this->portal->getDefaultLocalization()->willReturn(new Localization('de', 'at'));

        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal());

        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->defaultLocaleProvider = new RequestDefaultLocaleProvider(
            $this->requestAnalyzer->reveal(),
            $this->requestStack->reveal()
        );
    }

    public function testGetDefaultLocale(): void
    {
        $this->portal->getLocalizations()->willReturn([
            new Localization('de', 'de'),
            new Localization('en'),
        ]);

        $request = $this->prophesize(Request::class);
        $request->getPreferredLanguage(['de_AT', 'de_DE', 'en'])->shouldBeCalled()->willReturn('en');
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $defaultLocale = $this->defaultLocaleProvider->getDefaultLocale();
        $this->assertEquals('en', $defaultLocale->getLocale(Localization::ISO6391));
    }

    public function testGetDefaultLocaleWithoutRequest(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $defaultLocale = $this->defaultLocaleProvider->getDefaultLocale();
        $this->assertEquals('de-AT', $defaultLocale->getLocale(Localization::ISO6391));
    }
}
