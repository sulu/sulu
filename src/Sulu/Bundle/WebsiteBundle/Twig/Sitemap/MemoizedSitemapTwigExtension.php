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

/**
 * Provides memoized twig functions for sitemap.
 */
class MemoizedSitemapTwigExtension extends \Twig_Extension implements SitemapTwigExtensionInterface
{
    /**
     * @var SitemapTwigExtensionInterface
     */
    private $extension;

    /**
     * @var MemoizeInterface
     */
    private $memoizeCache;

    /**
     * @var int
     */
    private $lifeTime;

    /**
     * Constructor.
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

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->extension->getFunctions();
    }

    /**
     * {@inheritdoc}
     */
    public function sitemapUrlFunction($url, $locale = null, $webspaceKey = null)
    {
        return $this->memoizeCache->memoize([$this->extension, 'sitemapUrlFunction'], $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function sitemapFunction($locale = null, $webspaceKey = null)
    {
        return $this->memoizeCache->memoize([$this->extension, 'sitemapFunction'], $this->lifeTime);
    }
}
