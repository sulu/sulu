<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use PHPCR\RepositoryException;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The PortalRouteProvider should load the dynamic routes created by Sulu.
 */
class ContentRouteProvider implements RouteProviderInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(
        ContentMapperInterface $contentMapper,
        RequestAnalyzerInterface $requestAnalyzer
    ) {
        $this->contentMapper = $contentMapper;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * Finds the correct route for the current request.
     * It loads the correct data with the content mapper.
     *
     * @param Request $request A request against which to match.
     *
     * @return \Symfony\Component\Routing\RouteCollection with all Routes that
     *                                                    could potentially match $request. Empty collection if nothing can
     *                                                    match.
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();

        $htmlExtension = '.html';
        $resourceLocator = $this->requestAnalyzer->getResourceLocator();

        if (
            $this->requestAnalyzer->getMatchType() == RequestAnalyzerInterface::MATCH_TYPE_REDIRECT
            || $this->requestAnalyzer->getMatchType() == RequestAnalyzerInterface::MATCH_TYPE_PARTIAL
        ) {
            // redirect webspace correctly with language
            $collection->add(
                'redirect_' . uniqid(),
                $this->getRedirectWebSpaceRoute($request)
            );
        } elseif (
            $request->getRequestFormat() === 'html' &&
            substr($request->getPathInfo(), -strlen($htmlExtension)) === $htmlExtension
        ) {
            // redirect *.html to * (without url)
            $collection->add(
                'redirect_' . uniqid(),
                $this->getRedirectRoute(
                    $request,
                    $this->getUrlWithoutEndingTrailingSlash($resourceLocator)
                )
            );
        } else {
            // just show the page
            $portal = $this->requestAnalyzer->getPortal();
            $language = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();

            try {
                // load content by url ignore ending trailing slash
                $content = $this->contentMapper->loadByResourceLocator(
                    rtrim($resourceLocator, '/'),
                    $portal->getWebspace()->getKey(),
                    $language
                );

                if (
                    preg_match('/\/$/', $resourceLocator)
                    && $this->requestAnalyzer->getResourceLocatorPrefix()
                    && $content->getNodeState() === StructureInterface::STATE_PUBLISHED
                ) {
                    // redirect page to page without slash at the end
                    $collection->add(
                        'redirect_' . uniqid(),
                        $this->getRedirectWebSpaceRoute($request)
                    );
                } elseif (
                    $content->getNodeType() === Structure::NODE_TYPE_INTERNAL_LINK &&
                    $content->getNodeState() === StructureInterface::STATE_PUBLISHED
                ) {
                    // redirect internal link
                    $collection->add(
                        $content->getKey() . '_' . uniqid(),
                        $this->getRedirectRoute(
                            $request,
                            $this->requestAnalyzer->getResourceLocatorPrefix() . $content->getResourceLocator()
                        )
                    );
                } elseif (
                    $content->getNodeType() === Structure::NODE_TYPE_EXTERNAL_LINK &&
                    $content->getNodeState() === StructureInterface::STATE_PUBLISHED
                ) {
                    $collection->add(
                        $content->getKey() . '_' . uniqid(),
                        $this->getRedirectRoute($request, $content->getResourceLocator())
                    );
                } elseif (
                    $content->getNodeState() === StructureInterface::STATE_TEST ||
                    !$content->getHasTranslation() ||
                    !$this->checkResourceLocator()
                ) {
                    // error 404 page not published
                    throw new ResourceLocatorNotFoundException();
                } else {
                    // show the page
                    $collection->add(
                        $content->getKey() . '_' . uniqid(),
                        $this->getStructureRoute($request, $content)
                    );
                }
            } catch (ResourceLocatorNotFoundException $exc) {
                // just do not add any routes to the collection
            } catch (ResourceLocatorMovedException $exc) {
                // old url resource was moved
                $collection->add(
                    $exc->getNewResourceLocatorUuid() . '_' . uniqid(),
                    $this->getRedirectRoute(
                        $request,
                        $this->requestAnalyzer->getResourceLocatorPrefix() . $exc->getNewResourceLocator()
                    )
                );
            } catch (RepositoryException $exc) {
                // just do not add any routes to the collection
            }
        }

        return $collection;
    }

    /**
     * Find the route using the provided route name.
     *
     * @param string $name       the route name to fetch
     * @param array  $parameters DEPRECATED the parameters as they are passed
     *                           to the UrlGeneratorInterface::generate call
     *
     * @return \Symfony\Component\Routing\Route
     *
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException if
     *                                                                     there is no route with that name in this repository
     */
    public function getRouteByName($name, $parameters = [])
    {
        // TODO: Implement getRouteByName() method.
    }

    /**
     * Find many routes by their names using the provided list of names.
     *
     * Note that this method may not throw an exception if some of the routes
     * are not found or are not actually Route instances. It will just return the
     * list of those Route instances it found.
     *
     * This method exists in order to allow performance optimizations. The
     * simple implementation could be to just repeatedly call
     * $this->getRouteByName() while catching and ignoring eventual exceptions.
     *
     * @param array $names      the list of names to retrieve
     * @param array $parameters DEPRECATED the parameters as they are passed to
     *                          the UrlGeneratorInterface::generate call. (Only one array, not one
     *                          for each entry in $names.
     *
     * @return \Symfony\Component\Routing\Route[] iterable thing with the keys
     *                                            the names of the $names argument.
     */
    public function getRoutesByNames($names, $parameters = [])
    {
        // TODO
        return [];
    }

    /**
     * Checks if the resource locator is valid.
     * A resource locator with a slash only is not allowed, the only exception is when it is a single language
     * website, where the browser automatically adds the slash.
     *
     * @return bool
     */
    private function checkResourceLocator()
    {
        return !($this->requestAnalyzer->getResourceLocator() === '/'
            && $this->requestAnalyzer->getResourceLocatorPrefix());
    }

    /**
     * @param Request $request
     *
     * @return Route
     */
    protected function getRedirectWebSpaceRoute(Request $request)
    {
        // redirect by information from webspace config
        return new Route(
            $request->getPathInfo(), [
                '_controller' => 'SuluWebsiteBundle:Default:redirectWebspace',
                'url' => $this->requestAnalyzer->getPortalUrl(),
                'redirect' => $this->requestAnalyzer->getRedirect(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param $url
     *
     * @return Route
     */
    protected function getRedirectRoute(Request $request, $url)
    {
        // redirect to linked page
        return new Route(
            $request->getPathInfo(), [
                '_controller' => 'SuluWebsiteBundle:Default:redirect',
                'url' => $url,
            ]
        );
    }

    /**
     * @param Request            $request
     * @param StructureInterface $content
     *
     * @return Route
     */
    protected function getStructureRoute(Request $request, $content)
    {
        return new Route(
            $request->getPathInfo(), [
                '_controller' => $content->getController(),
                'structure' => $content,
            ]
        );
    }

    /**
     * @param $resourceLocator
     *
     * @return string
     */
    protected function getUrlWithoutEndingTrailingSlash($resourceLocator)
    {
        return rtrim(
            $this->requestAnalyzer->getResourceLocatorPrefix() . ($resourceLocator ? $resourceLocator : '/'),
            '/'
        );
    }
}
