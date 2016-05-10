<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait DetermineServerInfoTrait
{
    /**
     * @return ContainerInterface
     *
     * @throws \LogicException
     */
    abstract protected function getContainer();

    /**
     * Determine the absolute file path for the router script, using the
     * environment to choose a standard script if no custom router script is
     * specified.
     *
     * @param string|null  $router  File path of the custom router script, if
     *                              set by the user; otherwise null
     * @param string       $context The context of the application kernel
     * @param SymfonyStyle $io      An SymfonyStyle instance
     *
     * @return bool|string The absolute file path of the router script, or false
     *                     on failure
     */
    private function determineRouterScript($router, $context, SymfonyStyle $io)
    {
        if (null === $router) {
            $router = $this
                ->getContainer()
                ->get('kernel')
                ->locateResource(sprintf(
                    '@SuluCoreBundle/Resources/config/router_%s.php',
                    $context
                ))
            ;
        }

        if (false === $path = realpath($router)) {
            $io->error(sprintf('The given router script "%s" does not exist.', $router));

            return false;
        }

        return $path;
    }

    /**
     * Determine the web server port that should be used.
     *
     * @param string|null  $port    The port, if set by the user; otherwise null
     * @param string       $context The context of the application kernel
     *
     * @return string The web server port to use
     */
    private function determinePort($port, $context)
    {
        if (null === $port) {
            return 'website' === $context ? '8001' : '8000';
        }

        return $port;
    }
}
