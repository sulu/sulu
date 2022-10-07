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
use Sulu\Bundle\WebsiteBundle\Locale\PortalDefaultLocaleProvider;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;

class PortalDefaultLocaleProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetDefaultLocale(): void
    {
        $portal = $this->prophesize(Portal::class);
        $portal->getDefaultLocalization()->willReturn(new Localization('de', 'at'));

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getPortal()->willReturn($portal->reveal());

        $portalDefaultLocaleProvider = new PortalDefaultLocaleProvider($requestAnalyzer->reveal());

        $defaultLocale = $portalDefaultLocaleProvider->getDefaultLocale();

        $this->assertEquals('de-AT', $defaultLocale->getLocale(Localization::ISO6391));
    }
}
