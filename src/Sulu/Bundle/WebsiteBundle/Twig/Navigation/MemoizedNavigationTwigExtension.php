<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Navigation;

use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Cache\MemoizeTwigExtensionTrait;
use Twig\Extension\AbstractExtension;

/**
 * Provides memoized navigation functions.
 */
class MemoizedNavigationTwigExtension extends AbstractExtension
{
    use MemoizeTwigExtensionTrait;

    /**
     * @param NavigationTwigExtensionInterface $extension
     * @param MemoizeInterface $memoizeCache
     * @param $lifeTime
     */
    public function __construct(NavigationTwigExtensionInterface $extension, MemoizeInterface $memoizeCache, $lifeTime)
    {
        $this->extension = $extension;
        $this->memoizeCache = $memoizeCache;
        $this->lifeTime = $lifeTime;
    }
}
