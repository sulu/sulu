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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * This route loader is responsible for loading routes from a routing file, and adjust the routes in a way, so that
 * there will be an own route for every portal registered.
 */
class PortalLoader extends Loader
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var RouteCollection
     */
    private $collection;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $this->collection = new RouteCollection();

        $importedRoutes = $this->import($resource, null);

        foreach ($importedRoutes as $importedRouteName => $importedRoute) {
            $this->generatePortalRoutes($importedRoute, $importedRouteName);
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return $type === 'portal';
    }

    /**
     * @param $importedRoute
     * @param $importedRouteName
     */
    private function generatePortalRoutes(Route $importedRoute, $importedRouteName)
    {
        $portalInformationCollection = $this->webspaceManager->getPortalInformations($this->environment);
        foreach ($portalInformationCollection as $portalInformation) {
            if (false === strpos($portalInformation->getUrl(), '*')) {
                $route = $this->createRoute($importedRoute, $portalInformation, $importedRouteName);

                // deprecated only for backward-compatibility of route names
                $this->collection->add(sprintf('%s.%s', $portalInformation->getUrl(), $importedRouteName), $route);
                $this->collection->add(
                    sprintf(
                        '%s.%s.%s',
                        $portalInformation->getUrl(),
                        $portalInformation->getLocale(),
                        $importedRouteName
                    ),
                    $route
                );
                continue;
            }

            foreach ($portalInformation->getPortal()->getLocalizations() as $localization) {
                $route = $this->createLocalizedRoute(
                    $importedRoute,
                    $portalInformation,
                    $portalInformationCollection,
                    $localization,
                    $importedRouteName
                );
                $this->collection->add(
                    sprintf(
                        '%s.%s.%s',
                        $portalInformation->getUrl(),
                        $localization->getLocalization(),
                        $importedRouteName
                    ),
                    $route
                );
            }
        }
    }

    /**
     * Create a localized route.
     *
     * @param Route $importedRoute
     * @param PortalInformation $portalInformation
     * @param array $portalInformationCollection
     * @param Localization $localization
     * @param string $importedRouteName
     *
     * @return Route
     */
    private function createLocalizedRoute(
        Route $importedRoute,
        PortalInformation $portalInformation,
        array $portalInformationCollection,
        Localization $localization,
        $importedRouteName
    ) {
        $currentPortalInformation = $portalInformation;
        if (strpos($portalInformation->getUrl(), '*')) {
            $currentPortalInformation = $this->getPortalInformation($localization, $portalInformationCollection);
        }

        return $this->createRoute($importedRoute, $currentPortalInformation, $importedRouteName);
    }

    /**
     * Create a route.
     *
     * @param Route $importedRoute
     * @param PortalInformation $portalInformation
     * @param string $importedRouteName
     *
     * @return Route
     */
    private function createRoute(Route $importedRoute, PortalInformation $portalInformation, $importedRouteName)
    {
        $route = clone $importedRoute;
        $route->setHost($portalInformation->getHost());
        $route->setPath($portalInformation->getPrefix() . $route->getPath());

        if ($portalInformation->getType() === RequestAnalyzerInterface::MATCH_TYPE_PARTIAL) {
            $route->setDefaults(
                [
                    '_controller' => 'SuluWebsiteBundle:Redirect:redirectToRoute',
                    'route' => sprintf(
                        '%s.%s.%s',
                        $portalInformation->getRedirect(),
                        $portalInformation->getLocale(),
                        $importedRouteName
                    ),
                    'permanent' => true,
                ]
            );
        }

        return $route;
    }

    /**
     * Returns main portal-information for given locale.
     * If no main exists it returns the first portal-information which has the given locale.
     *
     * @param Localization $localization
     * @param PortalInformation[] $portalInformationCollection
     *
     * @return PortalInformation
     */
    private function getPortalInformation(Localization $localization, array $portalInformationCollection)
    {
        $result = null;
        foreach ($portalInformationCollection as $portalInformation) {
            if ($portalInformation->getLocale() === $localization->getLocalization()) {
                if ($portalInformation->isMain()) {
                    // if a main exists return always this
                    return $portalInformation;
                } elseif ($result === null) {
                    // otherwise return first portal-information which has the given locale
                    $result = $portalInformation;
                }
            }
        }

        return $result;
    }
}
