<?php
/**
 * Created by JetBrains PhpStorm.
 * User: danielrotter
 * Date: 22.07.13
 * Time: 08:40
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;


class Navigation
{
    /**
     * @var NavigationItem
     */
    protected $root;

    function __construct(NavigationItem $root = null)
    {
        if ($root == null) {
            $root = new NavigationItem('');
        }
        $this->root = $root;
    }

    /**
     * @return NavigationItem
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Merges the given navigation with this one and returns the result.
     * Works only if there are no duplicate of items in the same level.
     * @param Navigation $navigation
     * @return Navigation
     */
    public function merge(Navigation $navigation)
    {
        return new Navigation($this->getRoot()->merge($navigation->getRoot()));
    }
}