<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\Manager;

use Sulu\Bundle\MediaBundle\Media\ImageConverter\Command\CommandInterface;

/**
 * Defines the operations of the CommandManager
 * The CommandManager load dynamically services for the image manipulation.
 */
interface ManagerInterface
{
    /**
     * Return a service which converts an image.
     *
     * @param string $name
     *
     * @return CommandInterface
     */
    public function get($name);
}
