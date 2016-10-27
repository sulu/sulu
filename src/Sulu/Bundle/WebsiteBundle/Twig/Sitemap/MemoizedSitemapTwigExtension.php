<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Sitemap;

use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Cache\MemoizeTwigExtensionTrait;

/**
 * Provides memoized twig functions for sitemap.
 */
class MemoizedSitemapTwigExtension extends \Twig_Extension
{
    use MemoizeTwigExtensionTrait;

    /**
     * @param SitemapTwigExtensionInterface $extension
     * @param MemoizeInterface $memoizeCache
     * @param $lifeTime
     */
    public function __construct(SitemapTwigExtensionInterface $extension, MemoizeInterface $memoizeCache, $lifeTime)
    {
        $this->extension = $extension;
        $this->memoizeCache = $memoizeCache;
        $this->lifeTime = $lifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->extension->getName();
    }
}
