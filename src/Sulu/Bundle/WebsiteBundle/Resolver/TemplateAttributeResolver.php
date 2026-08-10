<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolve all needed template attributes.
 */
class TemplateAttributeResolver implements TemplateAttributeResolverInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    protected $requestAnalyzer;

    /**
     * @var RequestAnalyzerResolverInterface
     */
    protected $requestAnalyzerResolver;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * TemplateAttributeResolver constructor.
     *
     * @param string $environment
     */
    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        RequestAnalyzerResolverInterface $requestAnalyzerResolver,
        WebspaceManagerInterface $webspaceManager,
        RouterInterface $router,
        RequestStack $requestStack,
        protected $environment,
    ) {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->requestAnalyzerResolver = $requestAnalyzerResolver;
        $this->webspaceManager = $webspaceManager;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function resolve($customParameters = [])
    {
        $parameters = \array_merge(
            $this->getDefaultParameters(),
            $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)
        );

        if (!isset($customParameters['localizations'])) {
            $localizations = [];
            $urls = $customParameters['urls'] ?? $this->getUrls();

            foreach ($urls as $locale => $url) {
                $localizations[$locale] = [
                    'locale' => $locale,
                    'url' => $url,
                    'alternate' => true,
                ];
            }

            $customParameters['localizations'] = $localizations;
        }

        return \array_merge(
            $parameters,
            $customParameters
        );
    }

    /**
     * @return array
     */
    protected function getDefaultParameters()
    {
        return [
            'extension' => [
                'excerpt' => [
                ],
                'seo' => [
                ],
            ],
            'content' => [],
            'view' => [],
            'shadowBaseLocale' => null,
        ];
    }

    /**
     * @return array
     */
    protected function getUrls()
    {
        $request = $this->requestStack->getCurrentRequest();
        $urls = [];

        $portal = $this->requestAnalyzer->getPortal();

        if ($request && $request->attributes->get('_route') && null !== $portal) {
            $portalInformations = $this->webspaceManager->getPortalInformations($this->environment);
            $routeParams = $request->attributes->get('_route_params');
            $routeName = $request->attributes->get('_route');

            foreach ($portalInformations as $portalInformation) {
                if (
                    $portalInformation->getPortalKey() === $portal->getKey()
                    && RequestAnalyzerInterface::MATCH_TYPE_FULL === $portalInformation->getType()
                ) {
                    if (isset($routeParams['prefix'])) {
                        $routeParams['prefix'] = $portalInformation->getPrefix();
                    }

                    if (isset($routeParams['_locale'])) {
                        $routeParams['_locale'] = $portalInformation->getLocale();
                    }

                    $url = $this->router->generate(
                        $routeName,
                        $routeParams,
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    $urls[$portalInformation->getLocale()] = $url;
                }
            }
        }

        return $urls;
    }
}
