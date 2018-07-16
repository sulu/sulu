<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This trait adds options to an event.
 */
trait EventOptionsTrait
{
    /**
     * This array is used as key value storage for the options.
     *
     * @var OptionsResolver
     */
    protected $options;

    /**
     * Returns all the options for the event.
     *
     * @return OptionsResolver
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns the option with the given name.
     *
     * @param string $name The name of the option
     * @param mixed $default The return value in case the option is not set
     *
     * @return mixed The value of the option
     */
    public function getOption($name, $default = null)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return $default;
    }
}
