<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Symfony\Component\OptionsResolver\OptionsResolver;

class NullRouteGenerator implements RouteGeneratorInterface
{
    public function generate($entity, array $options)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsResolver(array $options)
    {
        return new OptionsResolver();
    }
}
