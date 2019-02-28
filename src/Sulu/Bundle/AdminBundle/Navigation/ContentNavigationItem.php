<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * Represents an item in an content navigation, which is usually displayed with a form in the user interface.
 */
class ContentNavigationItem
{
    const DISPLAY_NEW = 'new';

    const DISPLAY_EDIT = 'edit';

    /**
     * The id of the navigation item.
     *
     * @var string
     */
    private $id;

    /**
     * The name of the navigation item.
     *
     * @var string
     */
    private $name;

    /**
     * The action to execute.
     *
     * @var string
     */
    private $action;

    /**
     * The name of the component to start.
     *
     * @var string
     */
    private $component;

    /**
     * An array of options, which will be passed to the corresponding component.
     *
     * @var array
     */
    private $componentOptions = [];

    /**
     * Defines in which state the navigation item will be displayed (basically new, edit).
     *
     * @var array
     */
    private $display;

    /**
     * Defines if the navigation item is disabled.
     *
     * @var bool
     */
    private $disabled;

    /**
     * Defines if the relationship manager in the frontend should be resetted.
     *
     * @var bool
     */
    private $resetStore;

    /**
     * Defines position.
     *
     * @var int
     */
    private $position;

    /**
     * Defines conditions whether the tab will be displayed or not.
     *
     * @var DisplayCondition[]
     */
    private $displayConditions;

    /**
     * @var int
     */
    private $notificationBadge;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->display = [static::DISPLAY_NEW, static::DISPLAY_EDIT];
        $this->resetStore = true;
    }

    /**
     * Returns id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Returns name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set action.
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Returns component.
     *
     * @return string
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Set component.
     *
     * @param string $component
     *
     * @return $this
     */
    public function setComponent($component)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Returns component-options.
     *
     * @return array
     */
    public function getComponentOptions()
    {
        return $this->componentOptions;
    }

    /**
     * Set component-options.
     *
     * @param array $componentOptions
     *
     * @return $this
     */
    public function setComponentOptions($componentOptions)
    {
        $this->componentOptions = $componentOptions;

        return $this;
    }

    /**
     * Returns display.
     *
     * @return array
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set display.
     *
     * @param array $display
     *
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Returns disabled.
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Returns reset-store.
     *
     * @return bool
     */
    public function isResetStore()
    {
        return $this->resetStore;
    }

    /**
     * Set reset-store.
     *
     * @param bool $resetStore
     *
     * @return $this
     */
    public function setResetStore($resetStore)
    {
        $this->resetStore = $resetStore;

        return $this;
    }

    /**
     * Returns position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set position.
     *
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Returns display-conditions.
     *
     * @return DisplayCondition[]
     */
    public function getDisplayConditions()
    {
        return $this->displayConditions;
    }

    /**
     * Set display-conditions.
     *
     * @param DisplayCondition[] $displayConditions
     *
     * @return $this
     */
    public function setDisplayConditions(array $displayConditions)
    {
        $this->displayConditions = $displayConditions;

        return $this;
    }

    /**
     * Returns notification-badge.
     *
     * @return int
     */
    public function getNotificationBadge()
    {
        return $this->notificationBadge;
    }

    /**
     * Set notification-badge.
     *
     * @param int $notificationBadge
     *
     * @return $this
     */
    public function setNotificationBadge($notificationBadge)
    {
        $this->notificationBadge = $notificationBadge;

        return $this;
    }
}
