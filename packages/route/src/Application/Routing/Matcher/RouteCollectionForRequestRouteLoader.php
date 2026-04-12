<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\Routing\Matcher;

use Psr\Container\ContainerInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal This is an internal class overwrite by decorate the service and use the `RouteCollectionForRequestLoaderInterface` not this class.
 *
 * The RouteLoader requires that a previous request listener has set the webspace and slug attributes. In case of Sulu
 * this is done inside the PageBundle via a WebspaceRequestListener.
 */
final readonly class RouteCollectionForRequestRouteLoader implements RouteCollectionForRequestLoaderInterface
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private ContainerInterface $routeDefaultsProviderLocator,
        private RequestContext $requestContext,
    ) {
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $locale = $request->getLocale();
        $webspace = $request->attributes->get(RequestAttributeEnum::WEBSPACE->value);
        $slug = $request->attributes->get(RequestAttributeEnum::SLUG->value);

        if (null === $webspace) {
            // TODO remove this bridge the routing should not know about webspaces and the sulu routes attributes
            $suluAttribute = $request->attributes->get('_sulu');
            if ($suluAttribute instanceof RequestAttributes) {
                $portalInformation = $suluAttribute->getAttribute('portalInformation');

                if ($portalInformation instanceof PortalInformation) {
                    $webspace = $portalInformation->getWebspaceKey();

                    if ($webspace) {
                        $request->attributes->set(RequestAttributeEnum::WEBSPACE->value, $webspace);
                        $this->requestContext->setParameter(RequestAttributeEnum::WEBSPACE->value, $webspace);
                    }
                }

                $matchType = $suluAttribute->getAttribute('matchType');

                if (null === $slug
                    && RequestAnalyzerInterface::MATCH_TYPE_FULL === $matchType // only full matches should have a slug
                ) {
                    // Important: This fix is only possible because we check that the matchType
                    // ResourceLocator is not set for homepage, because it is an empty string
                    // and the constructor of the RequestAttributes filters the given attributes via array_filter
                    $slug = $suluAttribute->getAttribute('resourceLocator') ?? '/';

                    if ($slug) {
                        $request->attributes->set(RequestAttributeEnum::SLUG->value, $slug);
                        $this->requestContext->setParameter(RequestAttributeEnum::SLUG->value, $slug);
                    }
                }
            }
        }

        if ((null !== $webspace && !\is_string($webspace))
            || !\is_string($slug)
        ) {
            return new RouteCollection();
        }

        $route = $this->routeRepository->findFirstBy([
            'webspaceOrNull' => $webspace,
            'locale' => $locale,
            'slug' => \rawurldecode($slug),
        ], ['webspace' => 'desc']);

        if (null === $route) {
            return new RouteCollection();
        }

        $routeDefaultsProvider = $this->routeDefaultsProviderLocator->get($route->getResourceKey());
        \assert($routeDefaultsProvider instanceof RouteDefaultsProviderInterface, 'The RouteDefaultsProvider must implement RouteDefaultsProviderInterface but got: ' . \get_debug_type($routeDefaultsProvider));
        $defaults = $routeDefaultsProvider->getDefaults($route);
        $defaults['_sulu_route'] = $route;

        $routeCollection = new RouteCollection();
        $symfonyRoute = new SymfonyRoute(
            \rawurldecode($request->getPathInfo()),
            $defaults,
            host: $request->getHost(),
        );

        $routeCollection->add('sulu_route.route_id_' . $route->getId(), $symfonyRoute);

        return $routeCollection;
    }
}
