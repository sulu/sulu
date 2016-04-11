<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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

    public function __construct($name)
    {
        $this->name = $name;
        $this->display = [static::DISPLAY_NEW, static::DISPLAY_EDIT];
        $this->resetStore = true;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * @param string $component
     */
    public function setComponent($component)
    {
        $this->component = $component;
    }

    /**
     * @return array
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param array $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getComponentOptions()
    {
        return $this->componentOptions;
    }

    /**
     * @param array $options
     */
    public function setComponentOptions(array $options)
    {
        $this->componentOptions = $options;
    }

    /**
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * @return bool
     */
    public function getResetStore()
    {
        return $this->resetStore;
    }

    /**
     * @param bool $resetStore
     */
    public function setResetStore($resetStore)
    {
        $this->resetStore = $resetStore;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return DisplayCondition[]
     */
    public function getDisplayConditions()
    {
        return $this->displayConditions;
    }

    /**
     * @param DisplayCondition[] $displayConditions
     */
    public function setDisplayConditions(array $displayConditions)
    {
        $this->displayConditions = $displayConditions;
    }
}
