<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing;

use PHPCR\Util\PathHelper;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Generates a list of custom routes based on custom url redirects that could match the current request.
 */
class CustomUrlRouteProvider implements RouteProviderInterface
{
    /**
     * @param array<string, string> $defaultOptions
     */
    public function __construct(
        private RequestAnalyzer $requestAnalyzer,
        private CustomUrlRepositoryInterface $customUrlRepository,
        private GeneratorInterface $generator,
        private array $defaultOptions,
        private WebspaceManagerInterface $webspaceManager,
        private PathBuilder $pathBuilder,
        private string $environment,
    ) {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $url = $this->sanitizeUrl($request);
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl($url, $this->environment);

        /** @var CustomUrl $customUrl */
        $customUrl = null;
        foreach ($portalInformations as $portalInformation) {
            if (!$portalInformation->getWebspace() || RequestAnalyzerInterface::MATCH_TYPE_WILDCARD !== $portalInformation->getType()) {
                continue;
            }

            if (null !== $requestAttributes = $this->matchCustomUrl($url, $portalInformation, $request)) {
                $customUrl = $requestAttributes['customUrl'];
                break;
            }
        }

        if (null === $customUrl) {
            return new RouteCollection();
        }

        if ($customUrl->isHistory()) {
            // If the custom url is an historic document check that the target document to prevent duplicate redirects
            if (!$customUrl->getTargetDocument()->getTargetDocument()->isRedirect()) {
                return $this->historicRedirectRouteCollection($request, $customUrl, $customUrl->getWebspace());
            }

            $routeDocument = $routeDocument->getTargetDocument();
            $customUrlDocument = $routeDocument->getTargetDocument();
        }

        // Forwarding the route to the normal request handling
        $collection = new RouteCollection();
        $collection->add(
            \uniqid('custom_url_route_', true),
            new Route(
                path: $this->decodePathInfo($request->getPathInfo()),
                defaults: [
                    '_custom_url' => $customUrl,
                    '_webspace' => $customUrl->getWebspace,
                    '_environment' => $this->environment,
                ],
                requirements: [],
                options: $this->defaultOptions
            )
        );

        return $collection;
    }

    /**
     * @param string $name
     */
    public function getRouteByName($name): Route
    {
        throw new RouteNotFoundException();
    }

    public function getRoutesByNames($names = null): iterable
    {
        return [];
    }

    /**
     * Add redirect to current custom-url.
     */
    private function historicRedirectRouteCollection(
        Request $request,
        CustomUrl $routeDocument,
        string $webspaceKey
    ): void {
        $routePath = $this->pathBuilder->build(['%base%', $webspaceKey, '%custom_urls%', '%custom_urls_routes%']);
        $resourceSegment = PathHelper::relativizePath($routeDocument->getTargetDocument()->getPath(), $routePath);

        $requestFormat = $request->getRequestFormat(null);
        $requestFormatSuffix = $requestFormat ? '.' . $requestFormat : '';

        $url = \sprintf('%s://%s%s', $request->getScheme(), $resourceSegment, $requestFormatSuffix);

        $collection = new RouteCollection();
        $collection->add(
            \uniqid('custom_url_route_', true),
            new Route(
                path: $this->decodePathInfo($request->getPathInfo()),
                defaults: [
                    '_controller' => [RedirectController::class, 'redirectAction'],
                    '_finalized' => true,
                    'url' => $url,
                ],
                requirements: [],
                options: $this->defaultOptions
            )
        );
    }

    private function matchCustomUrl(string $url, PortalInformation $portalInformation, Request $request): ?CustomUrl
    {
        dd($portalInformation);
        $webspace = $portalInformation->getWebspace();
        $customUrl = $this->customUrlRepository->findNewestPublishedByUrl(\rawurldecode($url), $webspace->getKey());

        if (!$customUrl) {
            return [];
        }

        $localization = Localization::createFromString($customUrl->getTargetLocale());

        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            $portalInformation->getWebspace()->getKey(),
            $localization->getLocale(),
            $this->environment
        );

        $this->requestAnalyzer->setAttributes(
            $this->requestAnalyzer->getAttributes()->merge([
                'portalInformation' => $portalInformation,
                'localization' => $localization,
                'locale' => $localization->getLocale(),
                'customUrl' => $customUrl,
                'urlExpression' => $this->generator->generate($customUrl->getBaseDomain(), $customUrl->getDomainParts()),
            ]));

        return $customUrl;
    }


    /**
     * Server encodes the url and symfony does not encode it
     * Symfony decodes this data here https://github.com/symfony/symfony/blob/3.3/src/Symfony/Component/Routing/Matcher/UrlMatcher.php#L91.
     *
     * @param string $pathInfo
     *
     * @return string
     */
    private function decodePathInfo(string $pathInfo): string
    {
        return \rawurldecode($pathInfo);
    }

    private function sanitizeUrl(Request $request): string
    {
        $pathInfo = $request->getPathInfo();

        // If the string contains a dot, strip everything after the dot. This should strip things like index.html to just index
        $position = \strrpos($pathInfo, '.');
        if ($position) {
            $pathInfo = \substr($pathInfo, 0, $position);
        }
        $pathInfo = \rtrim($pathInfo, '/');

        $queryString = $request->getQueryString() ?? '';
        if ('' !== $queryString) {
            $queryString = '?' . $queryString;
        }

        return $this->decodePathInfo(\sprintf('%s%s%s', $request->getHost(), $pathInfo, $queryString));
    }
}
