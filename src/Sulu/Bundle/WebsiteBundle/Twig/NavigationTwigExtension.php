<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use Sulu\Bundle\WebsiteBundle\Navigation\NavigationItem;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Localization;

/**
 * provides the navigation function
 * @package Sulu\Bundle\WebsiteBundle\Twig
 */
class NavigationTwigExtension extends \Twig_Extension
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var NavigationMapperInterface
     */
    private $navigationMapper;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    function __construct(
        ContentMapperInterface $contentMapper,
        NavigationMapperInterface $navigationMapper,
        RequestAnalyzerInterface $requestAnalyzer
    )
    {
        $this->contentMapper = $contentMapper;
        $this->navigationMapper = $navigationMapper;
        $this->requestAnalyzer = $requestAnalyzer;

    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('flat_root_navigation', array($this, 'flatRootNavigationFunction')),
            new \Twig_SimpleFunction('tree_root_navigation', array($this, 'treeRootNavigationFunction')),
            new \Twig_SimpleFunction('flat_navigation', array($this, 'flatNavigationFunction')),
            new \Twig_SimpleFunction('tree_navigation', array($this, 'treeNavigationFunction')),
            new \Twig_SimpleFunction('breadcrumb', array($this, 'breadcrumbFunction'))
        );
    }

    /**
     * Returns a flat navigation of first layer
     * @param string $context
     * @param int $depth
     * @param bool $loadExcerpt
     * @return NavigationItem[]
     */
    public function flatRootNavigationFunction($context = null, $depth = 1, $loadExcerpt = false)
    {
        $webspaceKey = $this->requestAnalyzer->getCurrentWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();

        return $this->navigationMapper->getRootNavigation($webspaceKey, $locale, $depth, true, $context, $loadExcerpt);
    }

    /**
     * Returns a tree navigation of first layer
     * @param string $context
     * @param int $depth
     * @param bool $loadExcerpt
     * @return NavigationItem[]
     */
    public function treeRootNavigationFunction($context = null, $depth = 1, $loadExcerpt = false)
    {
        $webspaceKey = $this->requestAnalyzer->getCurrentWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();

        return $this->navigationMapper->getRootNavigation($webspaceKey, $locale, $depth, false, $context, $loadExcerpt);
    }

    /**
     * Returns a flat navigation of children from given parent (uuid)
     * @param string $uuid
     * @param string $context
     * @param int $depth
     * @param bool $loadExcerpt
     * @param int $level
     * @return \Sulu\Bundle\WebsiteBundle\Navigation\NavigationItem[]
     */
    public function flatNavigationFunction($uuid, $context = null, $depth = 1, $loadExcerpt = false, $level = null)
    {
        $webspaceKey = $this->requestAnalyzer->getCurrentWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();

        if ($level !== null) {
            $breadcrumb = $this->contentMapper->loadBreadcrumb(
                $uuid,
                $locale,
                $webspaceKey
            );

            // return empty array if level does not exists
            if (!isset($breadcrumb[$level])) {
                return array();
            }

            $uuid = $breadcrumb[$level]->getUuid();
        }

        return $this->navigationMapper->getNavigation($uuid, $webspaceKey, $locale, $depth, true, $context, $loadExcerpt);
    }

    /**
     * Returns a tree navigation of children from given parent (uuid)
     * @param string $uuid
     * @param string $context
     * @param int $depth
     * @param bool $loadExcerpt
     * @param int $level
     * @return \Sulu\Bundle\WebsiteBundle\Navigation\NavigationItem[]
     */
    public function treeNavigationFunction($uuid, $context = null, $depth = 1, $loadExcerpt = false, $level = null)
    {
        $webspaceKey = $this->requestAnalyzer->getCurrentWebspace()->getKey();
        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();

        if ($level !== null) {
            $breadcrumb = $this->contentMapper->loadBreadcrumb(
                $uuid,
                $locale,
                $webspaceKey
            );

            // return empty array if level does not exists
            if (!isset($breadcrumb[$level])) {
                return array();
            }

            $uuid = $breadcrumb[$level]->getUuid();
        }

        return $this->navigationMapper->getNavigation($uuid, $webspaceKey, $locale, $depth, false, $context, $loadExcerpt);
    }

    /**
     * Returns breadcrumb for given node
     * @param $uuid
     * @param $webspaceKey
     * @param $localization
     * @return \Sulu\Bundle\WebsiteBundle\Navigation\NavigationItem[]
     */
    public function breadcrumbFunction($uuid, $webspaceKey, $localization)
    {
        return $this->navigationMapper->getBreadcrumb(
            $uuid,
            $webspaceKey,
            $localization
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_navigation';
    }
}
