<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\RestObject;

/**
 * Interface RestObjectInterface
 * The RestObject Interface manage the conversions from an entity to the rest object
 * @package Sulu\Bundle\MediaBundle\Media\RestObject
 */
interface RestObjectInterface {

    /**
     * will set the RestObject by an Entity Array
     * @param array $data
     * @param string $locale
     * @return mixed
     */
    public function setDataByEntityArray($data, $locale);

    /**
     * will set the RestObject by an Entity Object
     * @param $object
     * @param $locale
     * @return mixed
     */
    public function setDataByEntity($object, $locale);

    /**
     * will give back the RestObject as Array for the RestController
     * @param array $fields when empty all fields of the object will be returned
     * @return mixed
     */
    public function toArray($fields = array());
} 
