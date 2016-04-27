<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for request processors.
 * Provides functionality to process a single portal-information.
 */
abstract class AbstractRequestProcessor implements RequestProcessorInterface
{
    /**
     * Returns the request attributes for given portal information.
     *
     * @param Request $request
     * @param PortalInformation $portalInformation
     * @param array $additionalAttributes
     *
     * @return RequestAttributes
     */
    protected function processPortalInformation(
        Request $request,
        PortalInformation $portalInformation,
        $additionalAttributes = []
    ) {
        $attributes = ['requestUri' => $request->getUri()];

        if ($portalInformation === null) {
            return new RequestAttributes(array_merge($attributes, $additionalAttributes));
        }

        if (null !== $localization = $portalInformation->getLocalization()) {
            $request->setLocale($portalInformation->getLocalization()->getLocalization());
        }

        $attributes['portalInformation'] = $portalInformation;

        $attributes['getParameter'] = $request->query->all();
        $attributes['postParameter'] = $request->request->all();

        $attributes['matchType'] = $portalInformation->getType();
        $attributes['redirect'] = $portalInformation->getRedirect();
        $attributes['analyticsKey'] = $portalInformation->getAnalyticsKey();

        $attributes['portalUrl'] = $portalInformation->getUrl();
        $attributes['webspace'] = $portalInformation->getWebspace();
        $attributes['portal'] = $portalInformation->getPortal();

        if ($portalInformation->getType() === RequestAnalyzerInterface::MATCH_TYPE_REDIRECT) {
            return new RequestAttributes(array_merge($attributes, $additionalAttributes));
        }

        $attributes['localization'] = $portalInformation->getLocalization();
        $attributes['segment'] = $portalInformation->getSegment();

        list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
            $portalInformation,
            $request
        );

        $attributes['resourceLocator'] = $resourceLocator;
        $attributes['format'] = $format;

        $host = $request->getHost();
        if ($portalInformation->getType() !== RequestAnalyzerInterface::MATCH_TYPE_WILDCARD) {
            $urlInfo = parse_url($request->getScheme() . '://' . $portalInformation->getUrl());
            $host = $urlInfo['host'];
        }
        $attributes['resourceLocatorPrefix'] = substr($portalInformation->getUrl(), strlen($host));

        if (null !== $format) {
            $request->setRequestFormat($format);
        }

        return new RequestAttributes(array_merge($attributes, $additionalAttributes));
    }

    /**
     * Returns resource locator and format of current request.
     *
     * @param PortalInformation $portalInformation
     * @param Request $request
     *
     * @return array
     */
    private function getResourceLocatorFromRequest(PortalInformation $portalInformation, Request $request)
    {
        $path = $request->getPathInfo();

        // extract file and extension info
        $pathParts = explode('/', $path);
        $fileInfo = explode('.', array_pop($pathParts));

        $path = rtrim(implode('/', $pathParts), '/') . '/' . $fileInfo[0];
        $formatResult = null;
        if (count($fileInfo) > 1) {
            $formatResult = end($fileInfo);
        }

        $resourceLocator = substr(
            $request->getHost() . $path,
            strlen($portalInformation->getUrl())
        );

        return [$resourceLocator, $formatResult];
    }
}
