<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var RouteDocument
     */
    private $routeDocument;

    /**
     * @var CustomUrlDocument
     */
    private $customUrl;

    /**
     * @var string
     */
    private $route;

    public function __construct($route, RouteDocument $routeDocument, CustomUrlDocument $customUrl)
    {
        parent::__construct(
            sprintf('Cannot delete current route "%s" of custom-url "%s"', $route, $customUrl->getTitle()),
            9000
        );

        $this->route = $route;
        $this->routeDocument = $routeDocument;
        $this->customUrl = $customUrl;
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
