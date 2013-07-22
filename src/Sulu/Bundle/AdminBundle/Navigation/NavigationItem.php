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

    /**
     * @param $name The name of the item
     * @param NavigationItem $parent The parent of the item
     */
    function __construct($name, $parent = null)
    {
        $this->name = $name;

        if ($parent != null) {
            $parent->addChild($this);
        }
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

    /**
     * Returns all children from this navigation item
     * @return array
     */
    public function getChildren() {
        return $this->children;
    }

    /**
     * Returns a copy of this navigation item without its children
     * @return NavigationItem
     */
    public function copyChildless() {
        $new = new NavigationItem($this->getName());
        return $new;
    }

    /**
     * Compares this item with another one, but doesn't check the children
     * @param NavigationItem $other The other NavigationItem of the comparison
     * @return bool True if the NavigationItems are equal, otherwise false
     */
    public function equalsChildless(NavigationItem $other) {
        return $this->getName() == $other->getName();
    }

    /**
     * Searches for the equivalent of a specific NavigationItem
     * @param $navigationItem The NavigationItem to look for
     * @return NavigationItem The item if it is found, otherwise false
     */
    public function find($navigationItem) {
        $stack = array($this);
        while(!empty($stack)) {
            $item = array_pop($stack);
            if ($item->equalsChildless($navigationItem)) {
                return $item;
            }
            foreach ($item->getChildren() as $child) {
                $stack[] = $child;
            }
        }
        return null;
    }

    /**
     * Searches for a specific NavigationItem in the children of this NavigationItem.
     * @param $navigationItem The navigationItem we look for
     * @return Null if the NavigationItem is not found, otherwise the found NavigationItem.
     */
    public function findChildren($navigationItem) {
        foreach ($this->getChildren() as $child) {
            if ($child->equalsChildless($navigationItem)) {
                return $child;
            }
        }
        return null;
    }

    /**
     * Merges this navigation item with the other parameter and returns a new NavigationItem.
     * Works only if there are no duplicate values on one level.
     * @param NavigationItem $other The navigation item this one should be merged with
     */
    public function merge(NavigationItem $other = null) {
        //Create new item
        $new = $this->copyChildless();

        //Add all childs from this item
        foreach ($this->getChildren() as $child) {
            $new->addChild($child->merge(($other != null) ? $other->findChildren($child) : null));
        }

        //Add all childs from the other item
        if ($other != null) {
            foreach ($other->getChildren() as $child) {
                if (!$new->find($child)) {
                    $new->addChild($child->merge($this->copyChildless()));
                }
            }
        }

        return $new;
    }
}