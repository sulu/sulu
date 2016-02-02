<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

/**
 * The JsConfigPool is used to store parameters to be shown in index twig template.
 */
class JsConfigPool
{
    /**
     * The array for all the config-parameter.
     *
     * @var array
     */
    private $pool = [];

    /**
     * Returns all config parameters.
     *
     * @return array
     */
    public function getConfigParams()
    {
        return $this->pool;
    }

    /**
     * Adds a new config parameter.
     *
     * @param $params
     */
    public function addConfigParams(JsConfigInterface $params)
    {
        $this->pool = array_merge($this->pool, [$params->getName() => $params->getParameters()]);
    }
}
