<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads the routes of the two-factor profile endpoints only if a user is able to set up a two-factor method.
 *
 * @internal
 */
class TwoFactorRouteLoader extends Loader
{
    /**
     * @param string[] $twoFactorSetupMethods
     */
    public function __construct(private array $twoFactorSetupMethods)
    {
    }

    /**
     * @param string $resource
     * @param string|null $type
     */
    public function load($resource, $type = null): mixed
    {
        if ([] === $this->twoFactorSetupMethods) {
            return new RouteCollection();
        }

        return $this->import($resource);
    }

    /**
     * @param string $resource
     * @param string|null $type
     */
    public function supports($resource, $type = null): bool
    {
        return 'sulu_security_two_factor' === $type;
    }
}
