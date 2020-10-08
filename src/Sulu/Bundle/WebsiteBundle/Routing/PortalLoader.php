<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

    public function __construct(WebspaceManagerInterface $webspaceManager)
    {
        $this->webspaceManager = $webspaceManager;
    }

    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();

        /** @var Route[] $importedRoutes */
        $importedRoutes = $this->import($resource, null);

        $prefixes = [];
        foreach ($this->webspaceManager->getPortalInformations() as $portalInformation) {
            // symfony does not accept an empty regex as requirement, therefore we use '()' to match an empty prefix
            $prefixes[] = \preg_quote($portalInformation->getPrefix()) ?: '()';
        }

        foreach ($importedRoutes as $importedRouteName => $importedRoute) {
            $collection->add(
                $importedRouteName,
                new Route(
                    '{prefix}' . \ltrim($importedRoute->getPath(), '/'),
                    $importedRoute->getDefaults(),
                    \array_merge(['prefix' => \implode('|', $prefixes)], $importedRoute->getRequirements()),
                    $importedRoute->getOptions(),
                    $importedRoute->getHost(),
                    $importedRoute->getSchemes(),
                    $importedRoute->getMethods(),
                    $importedRoute->getCondition()
                )
            );
        }

        return $collection;
    }

    public function supports($resource, $type = null)
    {
        return 'portal' === $type;
    }
}
