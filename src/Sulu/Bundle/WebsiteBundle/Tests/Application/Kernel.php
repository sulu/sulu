<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Application;

use Sulu\Bundle\TestBundle\Kernel\SuluTestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class Kernel extends SuluTestKernel
{
    /**
     * @param string $environment
     * @param bool $debug
     * @param string $suluContext
     */
    public function __construct($environment, $debug, $suluContext = self::CONTEXT_ADMIN)
    {
        parent::__construct($environment, $debug, $suluContext);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $context = $this->getContext();
        $loader->load(__DIR__ . '/config/config_' . $context . '.yml');
    }
}
