<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\AdminBundle\Admin;

/**
 * Interface of Js Config
 *
 * @package Sulu\Bundle\AdminBundle\Admin
 */
interface JsConfigInterface
{
    /**
     * returns array of parameters
     */
    public function getParameters();

    public function getName();
}
