<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default request analyzer will be used for sulu-admin and extended for sulu-website.
 */
class RequestAnalyzer implements RequestAnalyzerInterface
{
    /**
     * @var RequestProcessorInterface[]
     */
    private $requestProcessors;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack, array $requestProcessors)
    {
        $this->requestStack = $requestStack;
        $this->requestProcessors = $requestProcessors;
    }

    public function analyze(Request $request)
    {
        if ($request->attributes->has('_sulu')) {
            return;
        }

        $attributes = new RequestAttributes(['scheme' => $request->getScheme(), 'requestUri' => $request->getRequestUri()]);
        foreach ($this->requestProcessors as $requestProcessor) {
            $attributes = $attributes->merge($requestProcessor->process($request, $attributes));
        }

        $request->attributes->set('_sulu', $attributes);
    }

    public function validate(Request $request)
    {
        $attributes = $request->attributes->get('_sulu');

        foreach ($this->requestProcessors as $provider) {
            $provider->validate($attributes);
        }
    }

    public function getAttribute($name, $default = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return $default;
        }

        if (!$request->attributes->has('_sulu')) {
            return $default;
        }

        return $request->attributes->get('_sulu')->getAttribute($name, $default);
    }

    public function getMatchType()
    {
        return $this->getAttribute('matchType');
    }

    public function getWebspace()
    {
        return $this->getAttribute('webspace');
    }

    public function getPortal()
    {
        return $this->getAttribute('portal');
    }

    public function getSegment()
    {
        return $this->getAttribute('segment');
    }

    public function getCurrentLocalization()
    {
        return $this->getAttribute('localization');
    }

    public function getPortalUrl()
    {
        return $this->getAttribute('portalUrl');
    }

    public function getRedirect()
    {
        return $this->getAttribute('redirect');
    }

    public function getResourceLocator()
    {
        return $this->getAttribute('resourceLocator', false);
    }

    public function getResourceLocatorPrefix()
    {
        return $this->getAttribute('resourceLocatorPrefix');
    }

    public function getPostParameters()
    {
        return $this->getAttribute('postParameter', []);
    }

    public function getGetParameters()
    {
        return $this->getAttribute('getParameter', []);
    }

    public function getAnalyticsKey()
    {
        return $this->getAttribute('analyticsKey', '');
    }

    public function getPortalInformation()
    {
        return $this->getAttribute('portalInformation');
    }
}
