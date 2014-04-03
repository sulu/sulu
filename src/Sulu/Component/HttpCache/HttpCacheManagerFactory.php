<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

use Psr\Log\NullLogger;
use Sulu\Component\HttpCache\Exception\NotImplementedHttpCacheManagerTypeException;
use Sulu\Component\HttpCache\Exception\UnknownHttpCacheManagerTypeException;

/**
 * Sulu cache manager factory
 */
class HttpCacheManagerFactory
{
    const SYMFONY_HTTP_CACHE = 'SymfonyHttpCache';
    const VARNISH_CACHE = 'VarnishCache';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param $logger
     */
    public function __construct($logger)
    {
        $this->logger = $logger ? : new NullLogger();
    }

    /**
     * @param $type
     * @return HttpCacheManagerInterface
     * @throws \Sulu\Component\HttpCache\Exception\NotImplementedHttpCacheManagerTypeException
     * @throws \Sulu\Component\HttpCache\Exception\UnknownHttpCacheManagerTypeException
     */
    public function get($type)
    {
        $instance = null;

        switch ($type) {

            case self::SYMFONY_HTTP_CACHE:
                $instance = new SymfonyHttpCacheManager($this->logger);
                break;

            case self::VARNISH_CACHE:
                throw new NotImplementedHttpCacheManagerTypeException();

            default:
                throw new UnknownHttpCacheManagerTypeException();
        }

        return $instance;
    }
}
