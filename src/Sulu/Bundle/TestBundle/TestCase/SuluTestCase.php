<?php

namespace Sulu\Bundle\TestBundle\TestCase;

use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RemoveForeignContextServicesPass;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SuluTestCase extends WebTestCase
{
    protected static function createKernel(array $options = [])
    {
        // See parent class implementation
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

        // Read sulu context from given option
        $suluContext = $options['sulu_context'] ?? SuluKernel::CONTEXT_ADMIN;

        return new static::$class($env, $debug, $suluContext);
    }

    protected static function createAuthenticatedClient(array $options = [], array $server = [])
    {
        return static::createClient(
            array_merge(
                [
                    [
                        'environment' => 'test',
                    ],
                    [
                        'PHP_AUTH_USER' => 'test',
                        'PHP_AUTH_PW' => 'test',
                    ],
                ],
                $options
            ),
            $server
        );
    }

    protected static function createAuthenticatedWebsiteClient(array $options = [], array $server = [])
    {
        return static::createAuthenticatedClient(
            array_merge(['sulu_context' => SuluKernel::CONTEXT_WEBSITE], $options),
            $server
        );
    }

    protected static function createWebsiteClient(array $options = [], array $server = [])
    {
        return static::createClient(
            array_merge(['sulu_context' => SuluKernel::CONTEXT_WEBSITE], $options),
            $server
        );
    }
}
