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

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
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

        /** @var Route[] $importedRoutes */
        $importedRoutes = $this->import($resource, null);

        foreach ($importedRoutes as $importedRouteName => $importedRoute) {
            $this->collection->add(
                $importedRouteName,
                new PortalRoute(
                    '{prefix}' . $importedRoute->getPath(),
                    $importedRoute->getDefaults(),
                    array_merge(['prefix' => '.*', 'host' => '.+'], $importedRoute->getRequirements()),
                    $importedRoute->getOptions(),
                    '{host}',
                    $importedRoute->getSchemes(),
                    $importedRoute->getMethods(),
                    $importedRoute->getCondition()
                )
            );
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
}
