<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * Represents an item in the navigation.
 * Contains the name and the coupled action for this specific NavigationItem.
 * @package Sulu\Bundle\AdminBundle\Navigation
 */
class NavigationItem implements \Iterator
{
    /**
     * The id of the NavigationItem
     * @var string
     */
    protected $id;

    /**
     * The name being displayed in the navigation
     * @var string
     */
    protected $name;

    /**
     * The icon of the navigationItem
     * @var string
     */
    protected $icon;

    /**
     * The action which should be executed when clicking on this NavigationItem
     * @var string
     */
    protected $action;

    /**
     * Contains the children of this item, which are other NavigationItems
     * @var array
     */
    protected $children = array();

    /**
     * The title of the head area of the NavigationItem
     * @var string
     */
    protected $headerTitle;

    /**
     * The icon of the header are of the NavigationItem
     * @var string
     */
    protected $headerIcon;

    /**
     * The current position of the iterator
     * @var integer
     */
    protected $position;

    /**
     * Defines if this menu item has settings
     * @var bool
     */
    protected $hasSettings;

    /**
     * The type of the content
     * @var string
     */
    protected $contentType;

    /**
     * Describes how the navigation item should be shown in husky
     * @var string
     */
    protected $displayOption;


    /**
     * @param string $name The name of the item
     * @param NavigationItem $parent The parent of the item
     * @param array $contentDisplay if null -> default is array('new', 'edit')
     */
    function __construct($name, $parent = null, array $contentDisplay = null)
    {
        $this->name = $name;

        if ($parent != null) {
            $parent->addChild($this);
        }
        if ($contentDisplay != null) {
            $this->contentDisplay = $contentDisplay;
        } else {
            $this->contentDisplay = array('new', 'edit');
        }
    }

    /**
     * Sets the id of the NavigationItem
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the id of the NavigationItem
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
     * Set the icon of the NavigaitonItem
     * @param string $icon The icon of the NavigationItem
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Returns the action of the NavigationItem
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }


    /**
     * Sets the action of the NavigationItem
     * @param String $action The action of the NavigationItem
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Returns the action of the NavigationItem
     * @return String
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Adds a child to the navigation item
     * @param NavigationItem $child
     */
    public function addChild(NavigationItem $child)
    {
        $this->children[] = $child;
    }

    /**
     * Returns all children from this navigation item
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets the icon of the header
     * @param string $headerIcon
     */
    public function setHeaderIcon($headerIcon)
    {
        $this->headerIcon = $headerIcon;
    }

    /**
     * Returns the icon of the header
     * @return string
     */
    public function getHeaderIcon()
    {
        return $this->headerIcon;
    }

    /**
     * Sets the title of the header
     * @param string $headerTitle The title of the header
     */
    public function setHeaderTitle($headerTitle)
    {
        $this->headerTitle = $headerTitle;
    }

    /**
     * Returns the title of the header
     * @return string The title of the header
     */
    public function getHeaderTitle()
    {
        return $this->headerTitle;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Checks if the NavigationItem has some children
     * @return bool True if the item has children, otherwise false
     */
    public function hasChildren()
    {
        return count($this->getChildren()) > 0;
    }

    /**
     * @param boolean $hasSettings
     */
    public function setHasSettings($hasSettings)
    {
        $this->hasSettings = $hasSettings;
    }

    /**
     * @return boolean
     */
    public function getHasSettings()
    {
        return $this->hasSettings;
    }

    /**
     * @param array $contentDisplay
     */
    public function setContentDisplay($contentDisplay)
    {
        $this->contentDisplay = $contentDisplay;
    }

    /**
     * @return array
     */
    public function getContentDisplay()
    {
        return $this->contentDisplay;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }


    /**
     * Returns a copy of this navigation item without its children
     * @return NavigationItem
     */
    public function copyChildless()
    {
        $new = new NavigationItem($this->getName());
        $new->setAction($this->getAction());
        $new->setIcon($this->getIcon());
        $new->setHeaderIcon($this->getHeaderIcon());
        $new->setHeaderTitle($this->getHeaderTitle());
        $new->setId($this->getId());
        $new->setHasSettings($this->getHasSettings());
        $new->setContentDisplay($this->getContentDisplay());
        $new->setContentType($this->getContentType());
        $new->setPosition($this->getPosition());

        return $new;
    }

    /**
     * Compares this item with another one, but doesn't check the children
     * @param NavigationItem $other The other NavigationItem of the comparison
     * @return bool True if the NavigationItems are equal, otherwise false
     */
    public function equalsChildless(NavigationItem $other)
    {
        return $this->getName() == $other->getName();
    }

    /**
     * Searches for the equivalent of a specific NavigationItem
     * @param NavigationItem $navigationItem The NavigationItem to look for
     * @return NavigationItem The item if it is found, otherwise false
     */
    public function find($navigationItem)
    {
        $stack = array($this);
        while (!empty($stack)) {
            /** @var NavigationItem $item */
            $item = array_pop($stack);
            if ($item->equalsChildless($navigationItem)) {
                return $item;
            }
            foreach ($item->getChildren() as $child) {
                /** @var NavigationItem $child */
                $stack[] = $child;
            }
        }

        return null;
    }

    /**
     * Searches for a specific NavigationItem in the children of this NavigationItem.
     * @param NavigationItem $navigationItem The navigationItem we look for
     * @return NavigationItem|null Null if the NavigationItem is not found, otherwise the found NavigationItem.
     */
    public function findChildren(NavigationItem $navigationItem)
    {
        foreach ($this->getChildren() as $child) {
            /** @var NavigationItem $child */
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
     * @return NavigationItem
     */
    public function merge(NavigationItem $other = null)
    {
        // Create new item
        $new = $this->copyChildless();

        // Add all children from this item
        foreach ($this->getChildren() as $child) {
            /** @var NavigationItem $child */
            $new->addChild($child->merge(($other != null) ? $other->findChildren($child) : null));
        }

        // Add all children from the other item
        if ($other != null) {
            foreach ($other->getChildren() as $child) {
                /** @var NavigationItem $child */
                if (!$new->find($child)) {
                    $new->addChild($child->merge($this->copyChildless()));
                }
            }
        }

        return $new;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->children[$this->position];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->position < sizeof($this->children);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Returns the content of the NavigationItem as array
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'title' => $this->getName(),
            'icon' => $this->getIcon(),
            'action' => $this->getAction(),
            'hasSettings' => $this->getHasSettings(),
            'id' => ($this->getId() != null) ? $this->getId() : uniqid(), //FIXME don't use uniqid()
        );

        // content specific options
        if (!!$this->getContentType()) {
            $array['contentType'] = $this->getContentType();
            $array['contentDisplay'] = $this->getContentDisplay();
        }

        if ($this->getHeaderIcon() != null || $this->getHeaderTitle() != null) {
            $array['header'] = array(
                'title' => $this->getHeaderTitle(),
                'logo' => $this->getHeaderIcon()
            );
        }


        foreach ($this->getChildren() as $key => $child) {
            /** @var NavigationItem $child */
            $array['items'][$key] = $child->toArray();
        }

        return $array;
    }
}
