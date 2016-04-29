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
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

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
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param ActiveTheme $activeTheme
     */
    public function __construct(RequestAnalyzerInterface $requestAnalyzer, ActiveTheme $activeTheme)
    {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->activeTheme = $activeTheme;
    }

    /**
     * Set the active theme if there is a portal.
     */
    public function setActiveThemeOnEngineInitialize()
    {
        $portal = $this->requestAnalyzer->getPortal();

        if (null === $portal) {
            return;
        }

        $themeKey = $portal->getWebspace()->getTheme()->getKey();
        $this->activeTheme->setName($themeKey);
    }

    /**
     * Set the active theme for a preview rendering.
     *
     * @param PreRenderEvent $event
     */
    public function setActiveThemeOnPreviewPreRender(PreRenderEvent $event)
    {
        $this->activeTheme->setName($event->getAttribute('webspace')->getTheme()->getKey());
    }
}
