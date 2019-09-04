<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait KernelTrait
{
    /**
     * @var ContainerInterface
     */
    protected static $container;

    protected static function getSuluContext(): string
    {
        return SuluKernel::CONTEXT_ADMIN;
    }

    protected static function createKernel(array $options = []): SuluKernel
    {
        $options = array_merge(static::getKernelConfiguration(), $options);

        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        if (isset($options['environment'])) {
            $env = $options['environment'];
        } elseif (isset($_ENV['APP_ENV'])) {
            $env = $_ENV['APP_ENV'];
        } elseif (isset($_SERVER['APP_ENV'])) {
            $env = $_SERVER['APP_ENV'];
        } else {
            $env = 'test';
        }

        if (isset($options['debug'])) {
            $debug = $options['debug'];
        } elseif (isset($_ENV['APP_DEBUG'])) {
            $debug = $_ENV['APP_DEBUG'];
        } elseif (isset($_SERVER['APP_DEBUG'])) {
            $debug = $_SERVER['APP_DEBUG'];
        } else {
            $debug = true;
        }

        if (isset($options['sulu.context'])) {
            $suluContext = $options['sulu.context'];
        } else {
            $suluContext = static::getSuluContext();
        }

        return new static::$class($env, (bool) $debug, $suluContext);
    }

    protected static function getKernelConfiguration(): array
    {
        return [];
    }
}
