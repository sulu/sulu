<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\TransformationInterface;

/**
 * Defines the operations of a pool containing transformations.
 */
interface TransformationPoolInterface
{
    /**
     * Return a service which transforms an image.
     *
     * @param string $name
     *
     * @return TransformationInterface
     */
    public function get($name);
}
