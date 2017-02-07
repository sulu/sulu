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

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Implements logic to provide the default locale based on the portal configuration.
 */
class PortalDefaultLocaleProvider implements DefaultLocaleProviderInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @param RequestAnalyzerInterface $requestAnalyzer
     */
    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocale()
    {
        return $this->requestAnalyzer->getPortal()->getDefaultLocalization();
    }
}
