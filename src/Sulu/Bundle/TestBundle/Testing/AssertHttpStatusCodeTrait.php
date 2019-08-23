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

use Symfony\Component\HttpFoundation\Response;

trait AssertHttpStatusCodeTrait
{
    /**
     * Assert the HTTP status code of a Response.
     *
     * If the response is not as expected we set the assertion message to the
     * body of the response - if it is json-decodable then we pretty print
     * JSON.
     *
     * The $debugLength argument limits the number of lines included from the
     * response body in case of failure.
     */
    protected static function assertHttpStatusCode(int $code, Response $response, int $debugLength = 50): void
    {
        $httpCode = $response->getStatusCode();

        $message = '';
        if ($code !== $httpCode) {
            $message = $response->getContent();

            if ('null' !== ($json = json_encode(json_decode($message, true), JSON_PRETTY_PRINT))
                && $json) {
                $message = explode(PHP_EOL, $json);
            } else {
                $message = explode(PHP_EOL, $message);
            }

            $message = implode(PHP_EOL, \array_slice($message, 0, $debugLength));
            $message = sprintf(
                'HTTP status code %s is not expected %s, showing %s lines of the response body: %s',
                $httpCode,
                $code,
                $debugLength,
                $message
            );
        }

        self::assertSame($code, $httpCode, $message);
    }
}
