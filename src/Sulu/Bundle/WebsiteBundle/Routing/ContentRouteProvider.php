<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Liip\ThemeBundle\ActiveTheme;
use PHPCR\RepositoryException;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Cmf\Bundle\RoutingBundle\Model\RedirectRoute;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The PortalRouteProvider should load the dynamic routes created by Sulu
 * @package Sulu\Bundle\WebsiteBundle\Routing
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
     *      could potentially match $request. Empty collection if nothing can
     *      match.
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();

        $htmlExtension = '.html';
        $resourceLocator = $this->requestAnalyzer->getCurrentResourceLocator();

        if ($this->requestAnalyzer->getCurrentMatchType() == RequestAnalyzerInterface::MATCH_TYPE_REDIRECT
            || $this->requestAnalyzer->getCurrentMatchType() == RequestAnalyzerInterface::MATCH_TYPE_PARTIAL
        ) {
            // redirect by information from webspace config
            $route = new Route(
                $request->getRequestUri(), array(
                    '_controller' => 'SuluWebsiteBundle:Default:redirectWebspace',
                    'url' => $this->requestAnalyzer->getCurrentPortalUrl(),
                    'redirect' => $this->requestAnalyzer->getCurrentRedirect()
                )
            );

            $collection->add('redirect_' . uniqid(), $route);
        } elseif (
            $request->getRequestFormat() === 'html' &&
            substr($request->getPathInfo(), -strlen($htmlExtension)) === $htmlExtension
        ) {
            $url = rtrim(
                $this->requestAnalyzer->getCurrentResourceLocatorPrefix() . ($resourceLocator ? $resourceLocator : '/'),
                '/'
            );

            // redirect *.html to * (without url)
            $route = new Route(
                $request->getRequestUri(), array(
                    '_controller' => 'SuluWebsiteBundle:Default:redirect',
                    'url' => $url
                )
            );

            $collection->add('redirect_' . uniqid(), $route);
        } else {
            // just show the page
            $portal = $this->requestAnalyzer->getCurrentPortal();
            $language = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();

            try {
                $content = $this->contentMapper->loadByResourceLocator(
                    $resourceLocator,
                    $portal->getWebspace()->getKey(),
                    $language
                );

                if (
                    $content->getNodeType() === Structure::NODE_TYPE_INTERNAL_LINK &&
                    $content->getNodeState() === StructureInterface::STATE_PUBLISHED
                ) {
                    // redirect to linked page
                    $route = new Route(
                        $request->getRequestUri(), array(
                            '_controller' => 'SuluWebsiteBundle:Default:redirect',
                            'url' => $this->requestAnalyzer->getCurrentResourceLocatorPrefix() . $content->getResourceLocator()
                        )
                    );

                    $collection->add($content->getKey() . '_' . uniqid(), $route);
                } elseif (
                    $content->getNodeState() === StructureInterface::STATE_TEST ||
                    !$content->getHasTranslation() ||
                    !$this->checkResourceLocator()
                ) {
                    throw new ResourceLocatorNotFoundException();
                } else {
                    $route = new Route(
                        $request->getPathInfo(), array(
                            '_controller' => $content->getController(),
                            'structure' => $content
                        )
                    );

                    $collection->add($content->getKey() . '_' . uniqid(), $route);
                }
            } catch (ResourceLocatorNotFoundException $exc) {
                // just do not add any routes to the collection
            } catch (ResourceLocatorMovedException $exc) {
                $newUrl = $this->requestAnalyzer->getCurrentResourceLocatorPrefix() . $exc->getNewResourceLocator();

                // redirect to new url
                $route = new Route(
                    $request->getRequestUri(), array(
                        '_controller' => 'SuluWebsiteBundle:Default:redirect',
                        'url' => $newUrl
                    )
                );

                $collection->add($exc->getNewResourceLocatorUuid() . '_' . uniqid(), $route);
            } catch (RepositoryException $exc) {
                // just do not add any routes to the collection
            }
        }

        return $collection;
    }

    /**
     * Find the route using the provided route name.
     *
     * @param string $name the route name to fetch
     * @param array $parameters DEPRECATED the parameters as they are passed
     *      to the UrlGeneratorInterface::generate call
     *
     * @return \Symfony\Component\Routing\Route
     *
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException if
     *      there is no route with that name in this repository
     */
    public function getRouteByName($name, $parameters = array())
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
     * @param array $names the list of names to retrieve
     * @param array $parameters DEPRECATED the parameters as they are passed to
     *      the UrlGeneratorInterface::generate call. (Only one array, not one
     *      for each entry in $names.
     *
     * @return \Symfony\Component\Routing\Route[] iterable thing with the keys
     *      the names of the $names argument.
     */
    public function getRoutesByNames($names, $parameters = array())
    {
        // TODO
        return array();
    }

    /**
     * Checks if the resource locator is valid.
     * A resource locator with a slash only is not allowed, the only exception is when it is a single language
     * website, where the browser automatically adds the slash
     * @return bool
     */
    private function checkResourceLocator()
    {
        return !($this->requestAnalyzer->getCurrentResourceLocator() === '/'
            && $this->requestAnalyzer->getCurrentResourceLocatorPrefix());
    }
}
