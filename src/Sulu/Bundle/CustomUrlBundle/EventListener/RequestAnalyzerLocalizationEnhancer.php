<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\EventListener;

use Sulu\Component\Webspace\Analyzer\EnhancerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Update localization in case of custom-url route.
 */
class RequestAnalyzerLocalizationEnhancer implements EnhancerInterface
{
    /**
     * Update locale in request analyzer.
     *
     * @param Request $request
     * @param RequestAnalyzerInterface $requestAnalyzer
     */
    public function enhance(Request $request, RequestAnalyzerInterface $requestAnalyzer)
    {
        if ($request->get('_custom_url') === null) {
            return;
        }

        $requestAnalyzer->updateLocale($request->get('_custom_url')->getTargetLocale(), $request);
    }
}
