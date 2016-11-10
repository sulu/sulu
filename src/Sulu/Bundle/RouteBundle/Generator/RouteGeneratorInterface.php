<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Interface for route-generation.
 */
interface RouteGeneratorInterface
{
    /**
     * Generates route by route-schema for given entity.
     *
     * @param object $entity
     * @param array $options
     *
     * @return string
     */
    public function generate($entity, array $options);

    /**
     * Returns options-resolver for validating options.
     *
     * @param array $options
     *
     * @return OptionsResolver
     */
    public function getOptionsResolver(array $options);
}
