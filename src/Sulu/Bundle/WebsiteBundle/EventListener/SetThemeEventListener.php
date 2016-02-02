<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Liip\ThemeBundle\ActiveTheme;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Listener which applies the configured theme.
 */
class SetThemeEventListener
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    /**
     * @param RequestAnalyzerInterface
     * @param ActiveTheme
     */
    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        ActiveTheme $activeTheme
    ) {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->activeTheme = $activeTheme;
    }

    /**
     * Set the active theme if there is a portal.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $portal = $this->requestAnalyzer->getPortal();

        if (null === $portal) {
            return;
        }

        $themeKey = $portal->getWebspace()->getTheme()->getKey();
        $this->activeTheme->setName($themeKey);
    }
}
