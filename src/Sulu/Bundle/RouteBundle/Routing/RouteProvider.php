<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Routing;

use PHPCR\Util\PathHelper;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Bundle\RouteBundle\Entity\Route as SuluRoute;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides symfony-routes by request or name.
 */
class RouteProvider implements RouteProviderInterface
{
    public const ROUTE_PREFIX = 'sulu_route_';

    /**
     * @var RouteRepositoryInterface
     */
    private $routeRepository;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RouteDefaultsProviderInterface
     */
    private $routeDefaultsProvider;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var LazyLoadingValueHolderFactory
     */
    private $proxyFactory;

    /**
     * @var array
     */
    private $defaultOptions;

    /**
     * @var Route[]
     */
    private $symfonyRouteCache = [];

    /**
     * @var SuluRoute[]
     */
    private $routeCache = [];

    public function __construct(
        RouteRepositoryInterface $routeRepository,
        RequestAnalyzerInterface $requestAnalyzer,
        RouteDefaultsProviderInterface $routeDefaultsProvider,
        RequestStack $requestStack,
        LazyLoadingValueHolderFactory $proxyFactory = null,
        array $defaultOptions = []
    ) {
        $this->routeRepository = $routeRepository;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->routeDefaultsProvider = $routeDefaultsProvider;
        $this->requestStack = $requestStack;
        $this->proxyFactory = $proxyFactory ?: new LazyLoadingValueHolderFactory();
        $this->defaultOptions = $defaultOptions;
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $collection = new RouteCollection();
        $path = $this->decodePathInfo($request->getPathInfo());

        /** @var RequestAttributes|null $attributes */
        $attributes = $request->attributes->get('_sulu');
        if (!$attributes) {
            return $collection;
        }

        $matchType = $attributes->getAttribute('matchType');
        if (RequestAnalyzerInterface::MATCH_TYPE_REDIRECT == $matchType
            || RequestAnalyzerInterface::MATCH_TYPE_PARTIAL == $matchType
        ) {
            return $collection;
        }

        $prefix = $attributes->getAttribute('resourceLocatorPrefix');

        if (!empty($prefix) && 0 === \strpos($path, $prefix)) {
            $path = PathHelper::relativizePath($path, $prefix);
        }

        // when the URI ends with a dot - symfony returns empty request-format
        if ('' === $format = $request->getRequestFormat()) {
            return $collection;
        }

        $path = $this->stripFormatExtension($path, $format);

        $route = $this->findRouteByPath($path, $request->getLocale());
        if ($route && \array_key_exists($route->getId(), $this->symfonyRouteCache)) {
            $collection->add(
                self::ROUTE_PREFIX . $route->getId(),
                $this->symfonyRouteCache[$route->getId()]
            );

            return $collection;
        }

        if (!$route
            || !$this->routeDefaultsProvider->supports($route->getEntityClass())
            || !$this->routeDefaultsProvider->isPublished(
                $route->getEntityClass(),
                $route->getEntityId(),
                $route->getLocale()
            )
        ) {
            return $collection;
        }

        $symfonyRoute = $this->createRoute($route, $request, $attributes);
        $routeObject = $symfonyRoute->getDefaults()['object'] ?? null;

        if ($routeObject instanceof ExtensionBehavior) {
            $portal = $attributes->getAttribute('portal');
            $documentSegments = $routeObject->getExtensionsData()['excerpt']['segments'] ?? [];
            $documentSegmentKey = $documentSegments[$portal->getWebspace()->getKey()] ?? null;
            $segment = $this->requestAnalyzer->getSegment();

            if ($segment && $documentSegmentKey && $segment->getKey() !== $documentSegmentKey) {
                $this->requestAnalyzer->changeSegment($documentSegmentKey);
            }
        }

        $collection->add(self::ROUTE_PREFIX . $route->getId(), $symfonyRoute);

        return $collection;
    }

    /**
     * Find route and cache it.
     *
     * @param string $path
     * @param string $locale
     *
     * @return SuluRoute
     */
    private function findRouteByPath($path, $locale)
    {
        $path = '/' . \ltrim($path, '/');
        if (!\array_key_exists($path, $this->routeCache)) {
            $this->routeCache[$path] = $this->routeRepository->findByPath($path, $locale);
        }

        return $this->routeCache[$path];
    }

    /**
     * @param string $name
     */
    public function getRouteByName($name): Route
    {
        if (0 !== \strpos($name, self::ROUTE_PREFIX)) {
            throw new RouteNotFoundException();
        }

        $request = $this->requestStack->getCurrentRequest();

        /** @var RequestAttributes $attributes */
        $attributes = $request->attributes->get('_sulu');
        if (!$attributes) {
            throw new RouteNotFoundException();
        }

        $routeId = \substr($name, \strlen(self::ROUTE_PREFIX));
        if (\array_key_exists($routeId, $this->symfonyRouteCache)) {
            return $this->symfonyRouteCache[$routeId];
        }

        /** @var RouteInterface $route */
        $route = $this->routeRepository->find($routeId);

        if (!$route
            || !$this->routeDefaultsProvider->supports($route->getEntityClass())
            || !$this->routeDefaultsProvider->isPublished(
                $route->getEntityClass(),
                $route->getEntityId(),
                $route->getLocale()
            )
        ) {
            throw new RouteNotFoundException();
        }

        return $this->createRoute($route, $request, $attributes);
    }

    public function getRoutesByNames($names = null): iterable
    {
        return [];
    }

    /**
     * Will create a symfony route.
     *
     * @return Route
     */
    protected function createRoute(RouteInterface $route, Request $request, RequestAttributes $attributes)
    {
        $routePath = $this->decodePathInfo($request->getPathInfo());

        $target = $route->getTarget();
        if ($route->isHistory() && $target) {
            return new Route(
                $routePath,
                [
                    '_controller' => 'sulu_website.redirect_controller::redirectAction',
                    'url' => $request->getSchemeAndHttpHost()
                        . $attributes->getAttribute('resourceLocatorPrefix')
                        . $target->getPath()
                        . ($request->getQueryString() ? ('?' . $request->getQueryString()) : ''),
                ],
                [],
                $this->defaultOptions
            );
        }

        $symfonyRoute = $this->proxyFactory->createProxy(
            Route::class,
            function(&$wrappedObject, LazyLoadingInterface $proxy, $method, array $parameters, &$initializer) use (
                $routePath,
                $route,
                $request
            ) {
                $initializer = null; // disable initialization
                $wrappedObject = new Route(
                    $routePath,
                    $this->routeDefaultsProvider->getByEntity(
                        $route->getEntityClass(),
                        $route->getEntityId(),
                        $request->getLocale()
                    ),
                    [],
                    $this->defaultOptions
                );

                return true;
            }
        );

        return $this->symfonyRouteCache[$route->getId()] = $symfonyRoute;
    }

    /**
     * Server encodes the url and symfony does not encode it
     * Symfony decodes this data here https://github.com/symfony/symfony/blob/3.3/src/Symfony/Component/Routing/Matcher/UrlMatcher.php#L91.
     *
     * @return string
     */
    private function decodePathInfo($pathInfo)
    {
        if (null === $pathInfo || '' === $pathInfo) {
            return '';
        }

        return '/' . \ltrim(\rawurldecode($pathInfo), '/');
    }

    /**
     * Return the given path without the format extension.
     *
     * @param string $path
     * @param string $format
     *
     * @return string
     */
    private function stripFormatExtension($path, $format)
    {
        $extension = '.' . $format;
        if (\substr($path, -\strlen($extension)) === $extension) {
            $path = \substr($path, 0, \strlen($path) - \strlen($extension));
        }

        return $path;
    }
}
