<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Structure for template
 */
interface StructureInterface {

    /**
     * key of template definition
     * @return string
     */
    public function getKey();

    /**
     * twig template of template definition
     * @return string
     */
    public function getView();

    /**
     * controller which renders the template definition
     * @return string
     */
    public function getController();

    /**
     * cacheLifeTime of template definition
     * @return int
     */
    public function getCacheLifeTime();

    /**
     * returns a property instance with given name
     * @param $name string name of property
     * @return PropertyInterface
     * @throws NoSuchPropertyException
     */
    public function getProperty($name);

    /**
     * returns an array of properties
     * @return array
     */
    public function getProperties();
}
