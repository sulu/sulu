<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document;

use Sulu\Component\Content\Document\Behavior\RobotBehavior;
use Sulu\Component\Content\Document\Behavior\TargetBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * Interface for custom-url document.
 */
interface CustomUrlBehavior extends UuidBehavior, PathBehavior, RobotBehavior, TargetBehavior
{
    /**
     * Returns title of custom-url.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Returns state of custom-url.
     *
     * @return string
     */
    public function isPublished();

    /**
     * Returns base domain of custom-url.
     *
     * @return string
     */
    public function getBaseDomain();

    /**
     * Returns domain parts of custom-url.
     *
     * @return array
     */
    public function getDomainParts();

    /**
     * Returns locale for target.
     *
     * @return string
     */
    public function getTargetLocale();

    /**
     * Returns true if canonical is enabled.
     *
     * @return bool
     */
    public function isCanonical();

    /**
     * Returns true if redirect is enabled.
     *
     * @return bool
     */
    public function isRedirect();

    /**
     * Returns list of existing routes.
     *
     * @return RouteDocument[]
     */
    public function getRoutes();

    /**
     * Set list of routes.
     *
     * @param RouteDocument[] $routes
     */
    public function setRoutes(array $routes);

    /**
     * Add a route to document.
     *
     * @param string $route
     * @param RouteDocument $routeDocument
     */
    public function addRoute($route, RouteDocument $routeDocument);
}
