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

use Symfony\Component\HttpFoundation\RedirectResponse;
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
    protected static function assertHttpStatusCode(int $code, $response, int $debugLength = 50): void
    {
        self::assertInstanceOf(Response::class, $response);

        $httpCode = $response->getStatusCode();

        $message = '';
        if ($code !== $httpCode) {
            if ($response instanceof RedirectResponse) {
                $message = \sprintf(
                    'Unexpected "%s" status code with redirect to "%s".',
                    $httpCode,
                    $response->getTargetUrl()
                );
            } else {
                $message = $response->getContent();

                if ('null' !== ($json = \json_encode(\json_decode($message, true), \JSON_PRETTY_PRINT))
                    && $json) {
                    $message = \explode(\PHP_EOL, $json);
                } else {
                    $message = \explode(\PHP_EOL, $message);
                }

                $message = \implode(\PHP_EOL, \array_slice($message, 0, $debugLength));

                $message = \sprintf(
                    'Got HTTP status code %s, but expected %s!' . \PHP_EOL
                    . 'Exception: %s' . \PHP_EOL
                    . 'Exception-File: %s' . \PHP_EOL
                    . 'Showing %s lines of the response body:' . \PHP_EOL
                    . '%s',
                    $httpCode,
                    $code,
                    \rawurldecode($response->headers->get('X-Debug-Exception')),
                    \rawurldecode($response->headers->get('X-Debug-Exception-File')),
                    $debugLength,
                    $message
                );
            }
        }

        self::assertSame($code, $httpCode, $message);
    }
}
