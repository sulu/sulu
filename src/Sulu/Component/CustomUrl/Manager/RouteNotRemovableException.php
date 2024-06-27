<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\Rest\Exception\RestException;

/**
 * Thrown when a current route will be deleted.
 */
class RouteNotRemovableException extends RestException
{
    /**
     * @param string $route
     */
    public function __construct(
        private $route,
        private RouteDocument $routeDocument,
        private CustomUrlDocument $customUrl,
    ) {
        parent::__construct(
            \sprintf('Cannot delete current route "%s" of custom-url "%s"', $route, $customUrl->getTitle()),
            9000
        );
    }

    /**
     * @return RouteDocument
     */
    public function getRouteDocument()
    {
        return $this->routeDocument;
    }

    /**
     * @return CustomUrlDocument
     */
    public function getCustomUrl()
    {
        return $this->customUrl;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
}
