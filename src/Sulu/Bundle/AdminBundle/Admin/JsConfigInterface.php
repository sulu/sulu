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
 * Interface of Js Config.
 */
interface JsConfigInterface
{
    /**
     * returns array of parameters.
     *
     * @return array
     */
    public function getParameters();

    /**
     * returns the identifying name.
     *
     * @return string
     */
    public function getName();
}
