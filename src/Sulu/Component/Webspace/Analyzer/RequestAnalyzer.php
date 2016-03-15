<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default request analyzer will be used for sulu-admin and extended for sulu-website.
 */
class RequestAnalyzer implements RequestAnalyzerInterface
{
    /**
     * @var RequestProcessorInterface[]
     */
    private $requestAttributesProvider;

    /**
     * @var RequestAttributes
     */
    private $attributes;

    public function __construct(array $requestAttributesProvider)
    {
        $this->requestAttributesProvider = $requestAttributesProvider;
        $this->attributes = new RequestAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(Request $request)
    {
        $this->attributes = $this->createAttributes($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes->getAttribute($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchType()
    {
        return $this->getAttribute('matchType');
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspace()
    {
        return $this->getAttribute('webspace');
    }

    /**
     * {@inheritdoc}
     */
    public function getPortal()
    {
        return $this->getAttribute('portal');
    }

    /**
     * {@inheritdoc}
     */
    public function getSegment()
    {
        return $this->getAttribute('segment');
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization()
    {
        return $this->getAttribute('localization');
    }

    /**
     * {@inheritdoc}
     */
    public function getPortalUrl()
    {
        return $this->getAttribute('portalUrl');
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirect()
    {
        return $this->getAttribute('redirect');
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceLocator()
    {
        return $this->getAttribute('resourceLocator');
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceLocatorPrefix()
    {
        return $this->getAttribute('resourceLocatorPrefix');
    }

    /**
     * {@inheritdoc}
     */
    public function getPostParameters()
    {
        return $this->getAttribute('postParameter', []);
    }

    /**
     * {@inheritdoc}
     */
    public function getGetParameters()
    {
        return $this->getAttribute('getParameter', []);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticsKey()
    {
        return $this->getAttribute('analyticsKey', '');
    }

    /**
     * {@inheritdoc}
     */
    public function getPortalInformation()
    {
        return $this->getAttribute('portalInformation');
    }

    /**
     * Returns merged attributes from all providers.
     *
     * @param Request $request
     *
     * @return RequestAttributes
     */
    protected function createAttributes(Request $request)
    {
        $attributes = new RequestAttributes();
        foreach ($this->requestAttributesProvider as $provider) {
            $attributes = $attributes->merge($provider->process($request));
        }

        foreach ($this->requestAttributesProvider as $provider) {
            $provider->validate($attributes);
        }

        return $attributes;
    }
}
