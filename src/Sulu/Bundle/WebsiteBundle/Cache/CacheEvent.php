<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Cache;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * The event raised by the CacheController after successful cache clear.
 */
class CacheEvent extends Event
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request the request being processed
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the request that is being processed.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
