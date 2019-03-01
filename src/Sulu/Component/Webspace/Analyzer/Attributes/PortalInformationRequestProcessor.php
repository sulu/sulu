<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

        $attributes['portalInformation'] = $portalInformation;

        $attributes['getParameter'] = $request->query->all();
        $attributes['postParameter'] = $request->request->all();

        $attributes['matchType'] = $portalInformation->getType();
        $attributes['redirect'] = $portalInformation->getRedirect();
        $attributes['analyticsKey'] = $portalInformation->getAnalyticsKey();

        $attributes['portalUrl'] = $portalInformation->getUrl();
        $attributes['webspace'] = $portalInformation->getWebspace();
        $attributes['portal'] = $portalInformation->getPortal();

        if (RequestAnalyzerInterface::MATCH_TYPE_REDIRECT === $portalInformation->getType()) {
            return new RequestAttributes($attributes);
        }

        $attributes['localization'] = $portalInformation->getLocalization();

        if (!$attributes['localization'] && $portalInformation->getPortal()) {
            $attributes['localization'] = $portalInformation->getPortal()->getDefaultLocalization();
        }

        if ($attributes['localization']) {
            $attributes['locale'] = $attributes['localization']->getLocale();
            $request->setLocale($attributes['locale']);
        }

        $attributes['segment'] = $portalInformation->getSegment();

        list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
            $portalInformation,
            $request,
            $requestAttributes->getAttribute('path')
        );

        $attributes['urlExpression'] = $portalInformation->getUrlExpression();
        $attributes['resourceLocator'] = $resourceLocator;
        $attributes['format'] = $format;

        $attributes['resourceLocatorPrefix'] = substr($portalInformation->getUrl(), strlen($request->getHost()));

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
     * @param string $path
     *
     * @return array
     */
    private function getResourceLocatorFromRequest(PortalInformation $portalInformation, Request $request, $path)
    {
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
