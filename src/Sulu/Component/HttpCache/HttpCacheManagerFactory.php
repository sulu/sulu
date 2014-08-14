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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Component\HttpCache\Exception\NotImplementedHttpCacheManagerTypeException;
use Sulu\Component\HttpCache\Exception\UnknownHttpCacheManagerTypeException;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Sulu cache manager factory
 */
class HttpCacheManagerFactory
{
    const SYMFONY_HTTP_CACHE = 'SymfonyHttpCache';
    const VARNISH_CACHE = 'VarnishCache';

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param WebspaceManagerInterface $webspaceManager
     * @param null $logger
     */
    public function __construct(WebspaceManagerInterface $webspaceManager, $logger = null)
    {
        $this->webspaceManager = $webspaceManager;
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
                $instance = new SymfonyHttpCacheManager($this->webspaceManager, $this->logger);
                break;

            case self::VARNISH_CACHE:
                throw new NotImplementedHttpCacheManagerTypeException();

            default:
                throw new UnknownHttpCacheManagerTypeException();
        }

        return $instance;
    }
}
