<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

abstract class Structure implements StructureInterface
{
    private $key;
    private $view;
    private $controller;
    private $cacheLifeTime;

    private $properties = array();

    /**
     * @param $key string
     * @param $view string
     * @param $controller string
     * @param $cacheLifeTime int
     */
    public function __construct($key, $view, $controller, $cacheLifeTime)
    {
        $this->key = $key;
        $this->view = $view;
        $this->controller = $controller;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    /**
     * adds a property to structure
     * @param PropertyInterface $property
     */
    protected function add(PropertyInterface $property)
    {
        $this->properties[$property->getName()] = $property;
    }

    /**
     * key of template definition
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * twig template of template definition
     * @return string
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * controller which renders the template definition
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * cacheLifeTime of template definition
     * @return int
     */
    public function getCacheLifeTime()
    {
        return $this->cacheLifeTime;
    }

    /**
     * returns a property instance with given name
     * @param $name string name of property
     * @return PropertyInterface
     * @throws NoSuchPropertyException
     */
    public function getProperty($name)
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        } else {
            throw new NoSuchPropertyException();
        }
    }

    /**
     * returns an array of properties
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * magic getter
     * @param $property
     * @return mixed
     * @throws NoSuchPropertyException
     */
    public function __get($property)
    {
        return $this->getProperty($property);
    }

    /**
     * magic setter
     * @param $property
     * @param $value
     * @return mixed
     * @throws NoSuchPropertyException
     */
    public function __set($property, $value)
    {
        if (isset($this->properties[$property])) {
            return $this->getProperty($property)->setValue($value);
        } else {
            throw new NoSuchPropertyException();
        }
    }

    /**
     * magic isset
     * @param $property
     * @return bool
     */
    public function __isset($property)
    {
        if (isset($this->properties[$property])) {
            $value = $this->getProperty($property)->getValue();

            return $value != null;
        } else {
            return isset($this->$property);
        }
    }
}
