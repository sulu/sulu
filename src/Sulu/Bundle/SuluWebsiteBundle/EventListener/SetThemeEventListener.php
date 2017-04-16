<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Liip\ThemeBundle\ActiveTheme;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Listener which applies the configured theme
 *
 * @package Sulu\Bundle\WebsiteBundle\EventListener
 */
class SetThemeEventListener
{
    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        ActiveTheme $activeTheme
    )
    {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->activeTheme = $activeTheme;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $portal = $this->requestAnalyzer->getCurrentPortal();
        $themeKey = $portal->getWebspace()->getTheme()->getKey();

        $this->activeTheme->setName($themeKey);
    }
}
