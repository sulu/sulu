<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
    public function __construct(
        private RequestAnalyzerInterface $requestAnalyzer,
        private RequestStack $requestStack,
    ) {
    }

    public function getDefaultLocale()
    {
        $portal = $this->requestAnalyzer->getPortal();

        if (null === $portal) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return $portal->getDefaultLocalization();
        }

        $defaultLocalization = $portal->getDefaultLocalization()->getLocale(Localization::LCID);
        $localizations = [$defaultLocalization];

        foreach ($portal->getLocalizations() as $localization) {
            if ($localization->getLocale(Localization::LCID) !== $defaultLocalization) {
                $localizations[] = $localization->getLocale(Localization::LCID);
            }
        }

        $preferredLocale = $request->getPreferredLanguage($localizations);

        foreach ($portal->getLocalizations() as $localization) {
            if ($localization->getLocale(Localization::LCID) === $preferredLocale) {
                return $localization;
            }
        }

        return $portal->getDefaultLocalization();
    }
}
