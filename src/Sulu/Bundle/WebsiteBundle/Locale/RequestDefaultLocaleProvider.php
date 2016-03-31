<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Locale;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements logic to provide the default locale based on the request preferred language.
 */
class RequestDefaultLocaleProvider implements DefaultLocaleProviderInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param RequestStack $requestStack
     */
    public function __construct(RequestAnalyzerInterface $requestAnalyzer, RequestStack $requestStack)
    {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocale()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return $this->requestAnalyzer->getPortal()->getDefaultLocalization();
        }

        $defaultLocalization = $this->requestAnalyzer->getPortal()->getDefaultLocalization()->getLocale(Localization::LCID);
        $localizations = [$defaultLocalization];

        foreach ($this->requestAnalyzer->getPortal()->getLocalizations() as $localization) {
            if ($localization->getLocale(Localization::LCID) !== $defaultLocalization) {
                $localizations[] = $localization->getLocale(Localization::LCID);
            }
        }

        $preferredLocale = $this->requestStack->getCurrentRequest()->getPreferredLanguage($localizations);

        foreach ($this->requestAnalyzer->getPortal()->getLocalizations() as $localization) {
            if ($localization->getLocale(Localization::LCID) === $preferredLocale) {
                return $localization;
            }
        }

        return $this->requestAnalyzer->getPortal()->getDefaultLocalization();
    }
}
