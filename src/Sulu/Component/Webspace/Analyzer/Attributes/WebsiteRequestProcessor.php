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

use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts attributes from request for the sulu-website.
 */
class WebsiteRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request)
    {
        $attributes = [];

        $url = $request->getHost() . $request->getPathInfo();
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl(
            $url,
            $this->environment
        );

        $attributes['requestUri'] = $request->getUri();

        if ($portalInformation === null) {
            return new RequestAttributes($attributes);
        }

        $request->setLocale($portalInformation->getLocalization()->getLocalization());

        $attributes['portalInformation'] = $portalInformation;

        $attributes['getParameter'] = $request->query->all();
        $attributes['postParameter'] = $request->request->all();

        $attributes['matchType'] = $portalInformation->getType();
        $attributes['redirect'] = $portalInformation->getRedirect();
        $attributes['analyticsKey'] = $portalInformation->getAnalyticsKey();

        $attributes['portalUrl'] = $portalInformation->getUrl();
        $attributes['webspace'] = $portalInformation->getWebspace();

        if ($portalInformation->getType() === RequestAnalyzerInterface::MATCH_TYPE_REDIRECT) {
            return new RequestAttributes($attributes);
        }

        $attributes['localization'] = $portalInformation->getLocalization();
        $attributes['portal'] = $portalInformation->getPortal();
        $attributes['segment'] = $portalInformation->getSegment();

        list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
            $portalInformation,
            $request
        );

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
        if (null === $attributes->getAttribute('portalInformation')) {
            throw new UrlMatchNotFoundException($attributes->getAttribute('requestUri'));
        }

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
            $request->getHost() . $path,
            strlen($portalInformation->getUrl())
        );

        return [$resourceLocator, $formatResult];
    }
}
