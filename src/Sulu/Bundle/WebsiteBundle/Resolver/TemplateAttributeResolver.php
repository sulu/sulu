<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
     * @var string
     */
    protected $environment;

    /**
     * TemplateAttributeResolver constructor.
     *
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param RequestAnalyzerResolverInterface $requestAnalyzerResolver
     * @param WebspaceManagerInterface $webspaceManager
     * @param RouterInterface $router
     * @param RequestStack $requestStack
     * @param string $environment
     */
    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        RequestAnalyzerResolverInterface $requestAnalyzerResolver,
        WebspaceManagerInterface $webspaceManager,
        RouterInterface $router,
        RequestStack $requestStack,
        $environment
    ) {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->requestAnalyzerResolver = $requestAnalyzerResolver;
        $this->webspaceManager = $webspaceManager;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($customParameters = [])
    {
        $parameters = array_merge(
            $this->getDefaultParameters(),
            $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)
        );

        // Generate Urls
        if (!isset($customParameters['urls'])) {
            $customParameters['urls'] = $this->getUrls();
        }

        return array_merge(
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

        if ($request->get('_route')) {
            $portalInformations = $this->webspaceManager->getPortalInformations($this->environment);
            $routeParams = $request->get('_route_params');
            $routeName = $request->get('_route');

            foreach ($portalInformations as $portalInformation) {
                if (
                    $portalInformation->getPortalKey() === $this->requestAnalyzer->getPortal()->getKey()
                    && $portalInformation->getType() === RequestAnalyzerInterface::MATCH_TYPE_FULL
                ) {
                    if (isset($routeParams['prefix'])) {
                        $routeParams['prefix'] = $portalInformation->getPrefix();
                    }

                    $url = $this->router->generate(
                        $routeName,
                        $routeParams,
                        true
                    );

                    $urls[$portalInformation->getLocale()] = $url;
                }
            }
        }

        return $urls;
    }
}
