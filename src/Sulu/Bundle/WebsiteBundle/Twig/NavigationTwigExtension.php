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

use Sulu\Component\Content\StructureInterface;

/**
 * provides the navigation function
 * @package Sulu\Bundle\WebsiteBundle\Twig
 */
class NavigationTwigExtension extends \Twig_Extension
{

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
     * @param integer|null  $level
     * @return StructureInterface[]
     */
    public function navigationFunction(StructureInterface $content, $level = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_navigation';
    }
}
