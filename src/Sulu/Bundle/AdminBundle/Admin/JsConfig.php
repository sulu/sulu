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

class JsConfig implements JsConfigInterface
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * constructor.
     *
     * @param $bundleName
     * @param array $params
     */
    public function __construct($bundleName, array $params)
    {
        $this->name = $bundleName;
        $this->addParameters($params);
    }

    /**
     * returns array of parameters.
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * adds a single parameter.
     *
     * @param $name
     * @param $value
     */
    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * adds array of parameters.
     *
     * @param array $params
     *
     * @throws \InvalidArgumentException
     */
    public function addParameters(array $params)
    {
        if (!is_array($params)) {
            throw new \InvalidArgumentException('$params has to be an array');
        }
        $this->parameters = array_merge($this->parameters, $params);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
