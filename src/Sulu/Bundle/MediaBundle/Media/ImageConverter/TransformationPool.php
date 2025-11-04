<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Psr\Container\ContainerInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\TransformationInterface;

readonly class TransformationPool implements TransformationPoolInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function get($name)
    {
        if ($this->container->has($name)) {
            /** @var TransformationInterface $service */
            $service = $this->container->get($name);

            return $service;
        }

        throw new \InvalidArgumentException(
            \sprintf(
                'A image transformation transformation named "%s" does not exist.',
                $name
            )
        );
    }
}
