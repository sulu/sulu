<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Cache\MemoizeTwigExtensionTrait;
use Twig\Extension\AbstractExtension;

/**
 * Provides memoized Interface to load content.
 */
class MemoizedContentTwigExtension extends AbstractExtension
{
    use MemoizeTwigExtensionTrait;

    /**
     * @param ContentTwigExtensionInterface $extension
     * @param MemoizeInterface $memoizeCache
     * @param int $lifeTime
     */
    public function __construct(ContentTwigExtensionInterface $extension, MemoizeInterface $memoizeCache, $lifeTime)
    {
        $this->extension = $extension;
        $this->memoizeCache = $memoizeCache;
        $this->lifeTime = $lifeTime;
    }
}
