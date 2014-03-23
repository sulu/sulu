<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache\EventListener;

use Sulu\Component\Cache\CacheManagerInterface;
use Sulu\Component\Content\Event\ContentNodeEvent;

/**
 * This listener
 * @package Sulu\Component\Cache\EventListener
 */
class ContentNodeListener
{
    /**
     * @var \Sulu\Component\Cache\CacheManagerInterface
     */
    protected $cacheManager;

    /**
     * @param CacheManagerInterface $cacheManager
     */
    public function __construct(CacheManagerInterface $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param ContentNodeEvent $event
     */
    public function onContentNodeSave(ContentNodeEvent $event)
    {
        $this->cacheManager->expire($event->getNode());
    }
}
