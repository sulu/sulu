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
 * Adds more information about the Portal if the portalInformation attribute has already been set.
 */
class PortalInformationRequestProcessor implements RequestProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $portalInformation = $requestAttributes->getAttribute('portalInformation');

        if (!$portalInformation instanceof PortalInformation) {
            return new RequestAttributes();
        }

        $attributes = ['requestUri' => $request->getUri()];

        if (null !== $localization = $portalInformation->getLocalization()) {
            $request->setLocale($localization->getLocalization());
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
            return new RequestAttributes($attributes);
        }

        $attributes['localization'] = $portalInformation->getLocalization();
        $attributes['segment'] = $portalInformation->getSegment();

        list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
            $portalInformation,
            $request
        );

        $attributes['urlExpression'] = $portalInformation->getUrlExpression();
        $attributes['resourceLocator'] = $resourceLocator;
        $attributes['format'] = $format;

        $attributes['resourceLocatorPrefix'] = substr($portalInformation->getUrl(), strlen($request->getHttpHost()));

        if (null !== $format) {
            $request->setRequestFormat($format);
        }

        return new RequestAttributes($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        return true;
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
            $request->getHttpHost() . $path,
            strlen($portalInformation->getUrl())
        );

        return [$resourceLocator, $formatResult];
    }
}
