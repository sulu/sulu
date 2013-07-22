<?php
/**
 * Created by JetBrains PhpStorm.
 * User: danielrotter
 * Date: 22.07.13
 * Time: 08:35
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * Represents an item in the navigation.
 * Contains the name and the coupled action for this specific NavigationItem.
 * @package Sulu\Bundle\AdminBundle\Navigation
 */
class NavigationItem {
    /**
     * The name being displayed in the navigation
     * @var string
     */
    protected $name;

    protected $children = array();

    function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the name being displayed in the navigation
     * @param string $name The name being displayed in the navigation
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name being displayed in the navigation
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a child to the navigation item
     * @param NavigationItem $child
     */
    public function addChild(NavigationItem $child) {
        $this->children[] = $child;
    }
}