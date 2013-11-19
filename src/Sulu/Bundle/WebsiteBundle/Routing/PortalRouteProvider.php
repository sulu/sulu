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
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Portal\PortalManagerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * The PortalRouteProvider should load the dynamic routes created by Sulu
 * @package Sulu\Bundle\WebsiteBundle\Routing
 */
class PortalRouteProvider implements RouteProviderInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var PortalManagerInterface
     */
    private $portalManager;

    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    public function __construct(ContentMapperInterface $contentMapper, PortalManagerInterface $portalManager, ActiveTheme $activeTheme)
    {
        $this->contentMapper = $contentMapper;
        $this->portalManager = $portalManager;
        $this->activeTheme = $activeTheme;
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
        $path = $request->getRequestUri();
        $portal = $this->portalManager->getCurrentPortal();
        $language = 'en'; //TODO set current language correctly

        // Set current theme
        $this->activeTheme->setName($portal->getTheme()->getKey());

        $collection = new RouteCollection();

        try {
            $content = $this->contentMapper->loadByResourceLocator($path, $portal->getKey(), $language);

            $route = new Route($path, array(
                '_controller' => $content->getController(),
                'content' => $content
            ));

            $collection->add($content->getKey() . '_' . uniqid(), $route);
        } catch (ResourceLocatorNotFoundException $rlnfe) {
            $route = new Route($path, array(
                '_controller' => 'SuluWebsiteBundle:Default:error404',
                'path' => $path
            ));

            $collection->add('error404_' . uniqid(), $route);
        }

        return $collection;
    }

    /**
     * Find the route using the provided route name.
     *
     * @param string $name       the route name to fetch
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
     * @param array $names      the list of names to retrieve
     * @param array $parameters DEPRECATED the parameters as they are passed to
     *      the UrlGeneratorInterface::generate call. (Only one array, not one
     *      for each entry in $names.
     *
     * @return \Symfony\Component\Routing\Route[] iterable thing with the keys
     *      the names of the $names argument.
     */
    public function getRoutesByNames($names, $parameters = array())
    {
        // TODO: Implement getRoutesByNames() method.
    }
}
