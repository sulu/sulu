<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SystemCollections;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warm-up cache for system collections.
 */
class SystemCollectionCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var SystemCollectionManagerInterface
     */
    private $systemCollectionManager;

    public function __construct(SystemCollectionManagerInterface $systemCollectionManager)
    {
        $this->systemCollectionManager = $systemCollectionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->systemCollectionManager->warmUp();
    }
}
