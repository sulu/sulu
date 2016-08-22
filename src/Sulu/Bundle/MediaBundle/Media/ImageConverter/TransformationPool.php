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
 * Default implementation of transformation pool.
 */
class TransformationPool implements TransformationPoolInterface
{
    /**
     * @var TransformationInterface[]
     */
    private $transformations = [];

    /**
     * @param TransformationInterface $transformation
     * @param string $alias
     */
    public function add(TransformationInterface $transformation, $alias)
    {
        $this->transformations[$alias] = $transformation;
    }

    /**
     * @param string $name A String with the name of the image transformation to load
     *
     * @return TransformationInterface
     *
     * @throws \InvalidArgumentException If the transformation doesn't exist
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->transformations)) {
            return $this->transformations[$name];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'A image transformation transformation named "%s" does not exist.',
                $name
            )
        );
    }
}
