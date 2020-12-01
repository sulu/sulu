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
     * Method which converts parameters into request content to work around a regression in Symfony 5.2 where its not
     * longer possible to send float or integers as parameters.
     *
     * @param mixed[] $parameters
     * @param mixed[] $server
     */
    protected static function jsonRequest(KernelBrowser $client, string $method, string $uri, array $parameters = [], array $server = [], bool $changeHistory = true): Crawler
    {
        // Based on https://github.com/symfony/symfony/blob/v5.2.0/src/Symfony/Component/HttpFoundation/Request.php#L388-L404
        // the logic in symfony above we convert parameters to json content.
        switch (\strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                $content = \json_encode($parameters, \defined('JSON_THROW_ON_ERROR') ? \JSON_THROW_ON_ERROR : 0);
                $query = [];
                break;
            default:
                $content = null;
                $query = $parameters;
                break;
        }

        return $client->request(
            $method,
            $uri,
            $query,
            [],
            \array_merge([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], $server),
            $content,
            $changeHistory
        );
    }
}
