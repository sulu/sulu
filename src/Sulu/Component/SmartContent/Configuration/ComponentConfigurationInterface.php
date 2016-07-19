<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Configuration;

/**
 * Interface for component configuration.
 */
interface ComponentConfigurationInterface extends \JsonSerializable
{
    /**
     * Returns name of javascript component.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns options for javascript component.
     *
     * @return array
     */
    public function getOptions();
}
