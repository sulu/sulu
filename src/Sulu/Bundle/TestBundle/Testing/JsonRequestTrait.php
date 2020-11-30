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

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

trait JsonRequestTrait
{
    /**
     * @param mixed[] $parameters
     * @param mixed[] $server
     */
    protected static function jsonRequest(KernelBrowser $client, string $method, string $uri, array $parameters = [], array $server = [], bool $changeHistory = true): Crawler
    {
        $content = null;

        if ('GET' !== $method) {
            // JSON_THROW_ON_ERROR requires at least php 7.3
            $content = \json_encode($parameters, \defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0);
            $parameters = [];
        }

        return $client->request(
            $method,
            $uri,
            $parameters,
            [], // files are not supported for json requests
            \array_merge([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], $server),
            $content,
            $changeHistory
        );
    }
}
