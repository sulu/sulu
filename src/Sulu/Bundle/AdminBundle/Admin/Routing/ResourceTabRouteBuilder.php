<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

class ResourceTabRouteBuilder implements ResourceTabRouteBuilderInterface
{
    const VIEW = 'sulu_admin.resource_tabs';

    /**
     * @var Route
     */
    private $route;

    public function __construct(string $name, string $path)
    {
        // TODO If some of the Builder functions is called after the getRoute function, it will affect the outputted route.
        // Maybe create the route later, and make it immutable?
        $this->route = new Route($name, $path, static::VIEW);
    }

    public function setResourceKey(string $resourceKey): ResourceTabRouteBuilderInterface
    {
        $this->route->addOption('resourceKey', $resourceKey);

        return $this;
    }

    public function addLocales(array $locales): ResourceTabRouteBuilderInterface
    {
        $oldLocales = $this->route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $this->route->addOption('locales', $newLocales);

        return $this;
    }

    public function getRoute(): Route
    {
        if (!$this->route->getOption('resourceKey')) {
            throw new \DomainException(
                'A route for a ResourceTabs view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if ($this->route->getOption('locales') && false === strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a ResourceTabs view needs a ":locale" placeholder in its URL'
                . ' if some "locales" have been set.'
            );
        }

        if (!$this->route->getOption('locales') && false !== strpos($this->route->getPath(), ':locale')) {
            throw new \DomainException(
                'A route for a ResourceTabs view needs a ":locale" placeholder in its URL'
                . ' if some "locales" have been set.'
            );
        }

        return $this->route;
    }
}
