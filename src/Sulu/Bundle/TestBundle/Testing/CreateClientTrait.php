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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Client;

trait CreateClientTrait
{
    /**
     * @return Client|KernelBrowser
     */
    protected static function createAuthenticatedClient(array $options = [], array $server = [])
    {
        return static::createClient(
            $options,
            array_merge(
                [
                    'PHP_AUTH_USER' => 'test',
                    'PHP_AUTH_PW' => 'test',
                ],
                $server
            )
        );
    }

    /**
     * @return Client|KernelBrowser
     */
    protected static function createAuthenticatedWebsiteClient(array $options = [], array $server = [])
    {
        return static::createAuthenticatedClient(
            array_merge(['sulu.context' => SuluKernel::CONTEXT_WEBSITE], $options),
            $server
        );
    }

    /**
     * @return Client|KernelBrowser
     */
    protected static function createWebsiteClient(array $options = [], array $server = [])
    {
        return static::createClient(array_merge(['sulu.context' => SuluKernel::CONTEXT_WEBSITE], $options), $server);
    }
}
