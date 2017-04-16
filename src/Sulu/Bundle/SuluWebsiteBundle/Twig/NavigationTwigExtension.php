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
use Sulu\Component\Content\StructureInterface;

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

    function __construct(ContentMapperInterface $contentMapper, NavigationMapperInterface $navigationMapper)
    {
        $this->contentMapper = $contentMapper;
        $this->navigationMapper = $navigationMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('root_navigation', array($this, 'rootNavigationFunction')),
            new \Twig_SimpleFunction('navigation', array($this, 'navigationFunction')),
            new \Twig_SimpleFunction('breadcrumb', array($this, 'breadcrumbFunction'))
        );
    }

    /**
     * Returns navigation for content node at given level or (if level null) sub-navigation of page
     * @param StructureInterface $content
     * @param int $depth depth of navigation returned
     * @param bool $flat
     * @param string $context
     * @return NavigationItem[]
     */
    public function rootNavigationFunction(
        StructureInterface $content,
        $depth = 1,
        $flat = false,
        $context = null
    ) {
        return $this->navigationMapper->getRootNavigation(
            $content->getWebspaceKey(),
            $content->getLanguageCode(),
            $depth,
            $flat,
            $context
        );
    }

    /**
     * Returns navigation for content node at given level or (if level null) sub-navigation of page
     * @param StructureInterface $content
     * @param int $depth depth of navigation returned
     * @param integer|null $level
     * @param bool $flat
     * @param string $context
     * @return NavigationItem[]
     */
    public function navigationFunction(
        StructureInterface $content,
        $depth = 1,
        $level = null,
        $flat = false,
        $context = null
    ) {
        $uuid = $content->getUuid();
        if ($level !== null) {
            $breadcrumb = $this->contentMapper->loadBreadcrumb(
                $uuid,
                $content->getLanguageCode(),
                $content->getWebspaceKey()
            );

            // return empty array if level does not exists
            if(!isset($breadcrumb[$level])){
                return array();
            }

            $uuid = $breadcrumb[$level]->getUuid();
        }

        return $this->navigationMapper->getNavigation(
            $uuid,
            $content->getWebspaceKey(),
            $content->getLanguageCode(),
            $depth,
            $flat,
            $context
        );
    }

    /**
     * Returns breadcrumb for given node
     * @param StructureInterface $content
     * @return \Sulu\Bundle\WebsiteBundle\Navigation\NavigationItem[]
     */
    public function breadcrumbFunction(StructureInterface $content)
    {
        return $this->navigationMapper->getBreadcrumb(
            $content->getUuid(),
            $content->getWebspaceKey(),
            $content->getLanguageCode()
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
