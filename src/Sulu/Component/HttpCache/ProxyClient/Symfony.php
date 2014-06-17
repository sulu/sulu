<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\ProxyClient;

use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\Exception\MissingHostException;
use FOS\HttpCache\ProxyClient\AbstractProxyClient;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeInterface;
use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Message\RequestInterface;

/**
 * Symfony HTTP cache invalidator.
 */
class Symfony extends AbstractProxyClient implements PurgeInterface
{
    const HTTP_METHOD_PURGE = 'PURGE';

    /**
     * Constructor
     *
     * @param ClientInterface $client HTTP client (optional)
     * If no HTTP client is supplied, a default one will be created.
     */
    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ? : new Client();
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url)
    {
        $this->queueRequest(self::HTTP_METHOD_PURGE, $url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function sendRequests(array $requests)
    {
        $allRequests = array();

        foreach ($requests as $request) {
            /** @var RequestInterface $request */
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
     * {@inheritdoc}
     */
    protected function getAllowedSchemes()
    {
        return array('http');
    }
}
