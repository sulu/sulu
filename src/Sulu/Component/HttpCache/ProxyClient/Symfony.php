<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\ProxyClient;

use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\InvalidUrlException;
use FOS\HttpCache\Exception\ProxyResponseException;
use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use FOS\HttpCache\ProxyClient\ProxyClientInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\RequestInterface;

/**
 * Symfony HTTP cache invalidator.
 */
class Symfony implements ProxyClientInterface, PurgeInterface
{
    const HTTP_METHOD_PURGE = 'PURGE';

    /**
     * HTTP client.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * Request queue.
     *
     * @var array|RequestInterface[]
     */
    private $queue;

    /**
     * Constructor.
     *
     * @param ClientInterface $client HTTP client (optional). If no HTTP client
     *                                is supplied, a default one will be
     *                                created
     */
    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url, array $headers = [])
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $queue = $this->queue;
        if (0 === count($queue)) {
            return 0;
        }

        $this->queue = [];
        $this->sendRequests($queue);

        return count($queue);
    }

    /**
     * Add a request to the queue.
     *
     * @param string $method  HTTP method
     * @param string $url     URL
     * @param array  $headers HTTP headers
     */
    protected function queueRequest($method, $url, array $headers = [])
    {
        $this->queue[] = $this->createRequest($method, $url, $headers);
    }

    /**
     * Create request.
     *
     * @param string $method  HTTP method
     * @param string $url     URL
     * @param array  $headers HTTP headers
     *
     * @return RequestInterface
     */
    protected function createRequest($method, $url, array $headers = [])
    {
        return $this->client->createRequest($method, $url, $headers);
    }

    /**
     * Sends all requests to each caching proxy server.
     *
     * Requests are sent in parallel to minimise impact on performance.
     *
     * @param RequestInterface[] $requests Requests
     *
     * @throws ExceptionCollection
     */
    private function sendRequests(array $requests)
    {
        $allRequests = [];

        foreach ($requests as $request) {
            /* @var RequestInterface $request */
            $proxyRequest = $this->client->createRequest(
                $request->getMethod(),
                $request->getUrl(),
                $request->getHeaders()
            );
            $allRequests[] = $proxyRequest;
        }

        try {
            $this->client->send($allRequests);
        } catch (MultiTransferException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle request exception.
     *
     * @param MultiTransferException $exceptions
     *
     * @throws ExceptionCollection
     */
    protected function handleException(MultiTransferException $exceptions)
    {
        $collection = new ExceptionCollection();

        foreach ($exceptions as $exception) {
            if ($exception instanceof CurlException) {
                // Caching proxy unreachable
                $e = ProxyUnreachableException::proxyUnreachable(
                    $exception->getRequest()->getHost(),
                    $exception->getMessage(),
                    $exception
                );
            } elseif ($exception instanceof RequestException) {
                // Other error
                $e = ProxyResponseException::proxyResponse(
                    $exception->getRequest()->getHost(),
                    $exception->getCode(),
                    $exception->getMessage(),
                    $exception
                );
            } else {
                // Unexpected exception type
                $e = $exception;
            }

            $collection->add($e);
        }

        throw $collection;
    }

    /**
     * Filter a URL.
     *
     * Prefix the URL with "http://" if it has no scheme, then check the URL
     * for validity. You can specify what parts of the URL are allowed.
     *
     * @param string   $url
     * @param string[] $allowedParts Array of allowed URL parts (optional)
     *
     * @throws InvalidUrlException If URL is invalid, the scheme is not http or
     *                             contains parts that are not expected
     *
     * @return string The URL (with default scheme if there was no scheme)
     */
    protected function filterUrl($url, array $allowedParts = [])
    {
        // parse_url doesn’t work properly when no scheme is supplied, so
        // prefix URL with HTTP scheme if necessary.
        if (false === strpos($url, '://')) {
            $url = sprintf('%s://%s', $this->getDefaultScheme(), $url);
        }

        if (!$parts = parse_url($url)) {
            throw InvalidUrlException::invalidUrl($url);
        }
        if (empty($parts['scheme'])) {
            throw InvalidUrlException::invalidUrl($url, 'empty scheme');
        }

        if (!in_array(strtolower($parts['scheme']), $this->getAllowedSchemes())) {
            throw InvalidUrlException::invalidUrlScheme($url, $parts['scheme'], $this->getAllowedSchemes());
        }

        if (count($allowedParts) > 0) {
            $diff = array_diff(array_keys($parts), $allowedParts);
            if (count($diff) > 0) {
                throw InvalidUrlException::invalidUrlParts($url, $allowedParts);
            }
        }

        return $url;
    }

    /**
     * Get default scheme.
     *
     * @return string
     */
    protected function getDefaultScheme()
    {
        return 'http';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedSchemes()
    {
        return ['http'];
    }
}
