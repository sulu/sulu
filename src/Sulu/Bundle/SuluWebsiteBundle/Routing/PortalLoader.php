<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * This route loader is responsible for loading routes from a routing file, and adjust the routes in a way, so that
 * there will be an own route for every portal registered
 * @package Sulu\Bundle\WebsiteBundle\Routing
 */
class PortalLoader extends Loader
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(WebspaceManagerInterface $webspaceManager)
    {
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();

        $importedRoutes = $this->import($resource, null);

        foreach ($importedRoutes as $importedName => $importedRoute) {
            $collection->add($importedName, $importedRoute);
        }

        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return $type === 'portal';
    }
}
