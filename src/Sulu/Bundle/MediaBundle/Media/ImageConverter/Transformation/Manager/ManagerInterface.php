<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\Manager;

use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\TransformationInterface;

/**
 * Defines the operations of a manager handling transformations
 * The TransformationManager loads services for the image manipulation dynamically.
 */
interface ManagerInterface
{
    /**
     * Return a service which converts an image.
     *
     * @param string $name
     *
     * @return TransformationInterface
     */
    public function get($name);
}
