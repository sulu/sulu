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

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('navigation', array($this, 'navigationFunction'))
        );
    }

    /**
     * Returns navigation for content node at given level or (if level null) sub-navigation of page
     * @param StructureInterface $content
     * @param int $depth depth of navigation returned
     * @param integer|null $level
     * @return NavigationItem[]
     */
    public function navigationFunction(StructureInterface $content, $depth = 1, $level = null)
    {
        $uuid = $content->getUuid();
        if ($level !== null) {
            $breadcrumb = $this->contentMapper->loadBreadcrumb(
                $uuid,
                $content->getLanguageCode(),
                $content->getWebspaceKey()
            );
            $uuid = $breadcrumb[$level]->getUuid();
        }

        return $this->navigationMapper->getNavigation($uuid, $content->getWebspaceKey(), $content->getUuid(), $depth);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_navigation';
    }
}
