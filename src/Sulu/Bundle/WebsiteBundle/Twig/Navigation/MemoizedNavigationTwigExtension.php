<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Navigation;

use Sulu\Component\Cache\MemoizeInterface;

/**
 * Provides memoized navigation functions.
 */
class MemoizedNavigationTwigExtension extends \Twig_Extension implements NavigationTwigExtensionInterface
{
    /**
     * @var NavigationTwigExtensionInterface
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
    public function __construct(NavigationTwigExtensionInterface $extension, MemoizeInterface $memoizeCache, $lifeTime)
    {
        $this->extension = $extension;
        $this->memoizeCache = $memoizeCache;
        $this->lifeTime = $lifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function flatRootNavigationFunction($context = null, $depth = 1, $loadExcerpt = false)
    {
        return $this->memoizeCache->memoize([$this->extension, 'flatRootNavigationFunction'], $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function treeRootNavigationFunction($context = null, $depth = 1, $loadExcerpt = false)
    {
        return $this->memoizeCache->memoize([$this->extension, 'treeRootNavigationFunction'], $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function treeNavigationFunction($uuid, $context = null, $depth = 1, $loadExcerpt = false, $level = null)
    {
        return $this->memoizeCache->memoize([$this->extension, 'treeNavigationFunction'], $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function flatNavigationFunction($uuid, $context = null, $depth = 1, $loadExcerpt = false, $level = null)
    {
        return $this->memoizeCache->memoize([$this->extension, 'flatNavigationFunction'], $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function breadcrumbFunction($uuid)
    {
        return $this->memoizeCache->memoize([$this->extension, 'breadcrumbFunction'], $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function navigationIsActiveFunction($requestUrl, $itemUrl)
    {
        return $this->memoizeCache->memoize([$this->extension, 'navigationIsActiveFunction'], $this->lifeTime);
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
}
