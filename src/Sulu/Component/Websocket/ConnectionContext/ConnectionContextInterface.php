<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Websocket\ConnectionContext;

use Psr\Http\Message\RequestInterface;

/**
 * Represents a client websocket-session.
 */
interface ConnectionContextInterface
{
    /**
     * Returns query of the upgrade request.
     *
     * @return array
     */
    public function getQuery();

    /**
     * Returns upgrade request.
     *
     * @return RequestInterface
     */
    public function getRequest();

    /**
     * Returns unique id for session.
     *
     * @return string
     */
    public function getId();

    /**
     * Indicates that the context is valid.
     *
     * @return bool
     */
    public function isValid();

    /**
     * Get parameter with given name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name);

    /**
     * Get parameter with given name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Returns all parameters.
     *
     * @return array
     */
    public function all();

    /**
     * Set parameter with given name.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set($name, $value);

    /**
     * Clear all parameter.
     */
    public function clear();
}
