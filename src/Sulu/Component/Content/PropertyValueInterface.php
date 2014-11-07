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

/**
 * Interface PropertyValueInterface
 * @package Sulu\Component\Content
 */
interface PropertyValueInterface
{
    /**
     * @return Metadata
     */
    public function getMeta();

    /**
     * @param Metadata $meta
     * @return $this
     */
    public function setMeta(Metadata $meta);

    /**
     * @param $name
     * @return string
     */
    public function getAttribute($name);

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value);

    /**
     * @return mixed
     */
    public function getAttributes();

    /**
     * @return PropertyValueInterface[]
     */
    public function getChildren();

    /**
     * @param $value
     * @param PropertyValueInterface $value
     * @return $this
     */
    public function addChildren(PropertyValueInterface $value);

} 
