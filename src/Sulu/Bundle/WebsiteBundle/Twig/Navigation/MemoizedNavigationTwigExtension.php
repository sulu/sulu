<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Navigation;

use Sulu\Component\Cache\MemoizeInterface;

/**
 * Provides memoized navigation functions
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
    private $memoize;

    /**
     * @var int
     */
    private $lifeTime;

    /**
     * Constructor
     */
    function __construct(NavigationTwigExtensionInterface $extension, MemoizeInterface $memoize, $navigationLifeTime)
    {
        $this->extension = $extension;
        $this->memoize = $memoize;
    }

    /**
     * {@inheritdoc}
     */
    public function flatRootNavigationFunction($context = null, $depth = 1, $loadExcerpt = false)
    {
        return $this->memoize->memoize(array($this->extension, 'flatRootNavigationFunction'), $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function treeRootNavigationFunction($context = null, $depth = 1, $loadExcerpt = false)
    {
        return $this->memoize->memoize(array($this->extension, 'treeRootNavigationFunction'), $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function treeNavigationFunction($uuid, $context = null, $depth = 1, $loadExcerpt = false, $level = null)
    {
        return $this->memoize->memoize(array($this->extension, 'treeNavigationFunction'), $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function flatNavigationFunction($uuid, $context = null, $depth = 1, $loadExcerpt = false, $level = null)
    {
        return $this->memoize->memoize(array($this->extension, 'flatNavigationFunction'), $this->lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function breadcrumbFunction($uuid)
    {
        return $this->memoize->memoize(array($this->extension, 'breadcrumbFunction'), $this->lifeTime);
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
