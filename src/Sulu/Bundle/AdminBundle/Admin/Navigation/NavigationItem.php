<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Navigation;

/**
 * @implements \Iterator<int, self>
 */
class NavigationItem implements \Iterator
{
    /**
     * The id of the NavigationItem.
     *
     * @var string
     */
    protected $id;

    /**
     * The name being displayed in the navigation.
     *
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $label;

    /**
     * The icon of the navigationItem.
     *
     * @var string
     */
    protected $icon;

    /**
     * @var string|null
     */
    protected $view;

    /**
     * @var string[]
     */
    protected $childViews = [];

    /**
     * Contains the children of this item, which are other NavigationItems.
     *
     * @var array<self>
     */
    protected $children = [];

    /**
     * The current position of the iterator.
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Describes how the navigation item should be shown in husky.
     *
     * @var string
     */
    protected $displayOption;

    /**
     * Defines if item is disabled.
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * Defines if item is visible in the navigation.
     *
     * @var bool
     */
    protected $visible = true;

    /**
     * @param string $name The name of the item
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the id of the NavigationItem.
     *
     * @param string $id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the id of the NavigationItem.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the name being displayed in the navigation.
     *
     * @param string $name The name being displayed in the navigation
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name being displayed in the navigation.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setLabel(?string $label = null): void
    {
        $this->label = $label;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set the icon of the NavigaitonItem.
     *
     * @param string $icon The icon of the NavigationItem
     *
     * @return void
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Returns the icon of the NavigationItem.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    public function setView(?string $view = null): void
    {
        $this->view = $view;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    /**
     * @param string[] $childViews
     */
    public function setChildViews(array $childViews): void
    {
        $this->childViews = $childViews;
    }

    public function addChildView(string $childView): void
    {
        $this->childViews[] = $childView;
    }

    /**
     * @return string[]
     */
    public function getChildViews(): array
    {
        return $this->childViews;
    }

    /**
     * Adds a child to the navigation item.
     *
     * @return void
     */
    public function addChild(self $child)
    {
        $this->children[] = $child;
    }

    /**
     * Returns all children from this navigation item.
     *
     * @return self[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param int $position
     *
     * @return void
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
     * Checks if the NavigationItem has some children.
     *
     * @return bool True if the item has children, otherwise false
     */
    public function hasChildren()
    {
        return \count($this->getChildren()) > 0;
    }

    /**
     * @param bool $disabled
     *
     * @return void
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param bool $visible
     *
     * @return void
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
    }

    /**
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Returns a copy of this navigation item without its children.
     *
     * @return NavigationItem
     */
    public function copyChildless()
    {
        $new = $this->copyWithName();
        $new->setView($this->getView());
        $new->setChildViews($this->getChildViews());
        $new->setIcon($this->getIcon());
        $new->setId($this->getId());
        $new->setPosition($this->getPosition());
        $new->setLabel($this->getLabel());

        return $new;
    }

    /**
     * Create a new instance of current navigation item class.
     *
     * @return NavigationItem
     */
    protected function copyWithName()
    {
        return new self($this->getName());
    }

    /**
     * Compares this item with another one, but doesn't check the children.
     *
     * @param NavigationItem $other The other NavigationItem of the comparison
     *
     * @return bool True if the NavigationItems are equal, otherwise false
     */
    public function equalsChildless(self $other)
    {
        return $this->getName() == $other->getName();
    }

    /**
     * Searches for the equivalent of a specific NavigationItem.
     *
     * @param NavigationItem $navigationItem The NavigationItem to look for
     *
     * @return NavigationItem|null The item if it is found, otherwise null
     */
    public function find($navigationItem)
    {
        $stack = [$this];
        while (!empty($stack)) {
            /** @var NavigationItem $item */
            $item = \array_pop($stack);
            if ($item->equalsChildless($navigationItem)) {
                return $item;
            }
            foreach ($item->getChildren() as $child) {
                /* @var NavigationItem $child */
                $stack[] = $child;
            }
        }

        return null;
    }

    /**
     * Searches for a specific NavigationItem in the children of this NavigationItem.
     *
     * @param NavigationItem $navigationItem The navigationItem we look for
     *
     * @return NavigationItem|null Null if the NavigationItem is not found, otherwise the found NavigationItem
     */
    public function findChildren(self $navigationItem)
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
     * Return the current element.
     *
     * @see http://php.net/manual/en/iterator.current.php
     */
    public function current(): self
    {
        return $this->children[$this->position];
    }

    /**
     * Move forward to next element.
     *
     * @see http://php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element.
     *
     * @see http://php.net/manual/en/iterator.key.php
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid.
     *
     * @see http://php.net/manual/en/iterator.valid.php
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     *              Returns true on success or false on failure
     */
    public function valid(): bool
    {
        return $this->position < \count($this->children);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @see http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Returns the content of the NavigationItem as array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [
            'title' => $this->getName(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'view' => $this->getView(),
            'disabled' => $this->getDisabled(),
            'visible' => $this->getVisible(),
            'id' => (null != $this->getId()) ? $this->getId() : \str_replace('.', '', \uniqid('', true)), //FIXME don't use uniqid()
        ];

        if (\count($this->getChildViews()) > 0) {
            $array['childViews'] = $this->getChildViews();
        }

        $children = $this->getChildren();

        \usort($children, function(NavigationItem $a, NavigationItem $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        foreach ($children as $key => $child) {
            /* @var NavigationItem $child */
            $array['items'][$key] = $child->toArray();
        }

        return $array;
    }
}
