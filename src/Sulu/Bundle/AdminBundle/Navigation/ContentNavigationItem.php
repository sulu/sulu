<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * Represents an item in an content navigation, which is usually displayed with a form in the user interface
 * @package Sulu\Bundle\AdminBundle\Navigation
 */
class ContentNavigationItem
{
    const DISPLAY_NEW = 'new';

    const DISPLAY_EDIT = 'edit';

    /**
     * The id of the navigation item
     * @var string
     */
    private $id;

    /**
     * The name of the navigation item
     * @var string
     */
    private $name;

    /**
     * The action to execute
     * @var string
     */
    private $action;

    /**
     * An array of groups, which contain this navigation item.
     * This is used for filtering the items for the navigation.
     * @var array
     */
    private $groups;

    /**
     * The name of the component to start
     * @var string
     */
    private $component;

    /**
     * An array of options, which will be passed to the corresponding component
     * @var array
     */
    private $componentOptions;

    /**
     * Defines in which state the navigation item will be displayed (basically new, edit)
     * @var array
     */
    private $display;

    /**
     * Defines if the navigation item is disabled
     * @var boolean
     */
    private $disabled;

    /**
     * Defines if the relationship manager in the frontend should be resetted
     * @var boolean
     */
    private $resetStore;

    /**
     * @param $name
     */
    function __construct($name)
    {
        $this->name = $name;
        $this->display = array(static::DISPLAY_NEW, static::DISPLAY_EDIT);
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
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
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
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param boolean $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * @return boolean
     */
    public function getResetStore()
    {
        return $this->resetStore;
    }

    /**
     * @param boolean $resetStore
     */
    public function setResetStore($resetStore)
    {
        $this->resetStore = $resetStore;
    }

    /**
     * Returns an array representation of the content navigation item
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'id' => ($this->getId() != null) ? $this->getId() : uniqid(),
            'title' => $this->getName(),
            'action' => $this->getAction(),
            'display' => $this->getDisplay(),
            'component' => $this->getComponent(),
            'componentOptions' => $this->getComponentOptions(),
            'disabled' => $this->getDisabled(),
            'resetStore' => $this->getResetStore(),
        );

        return $array;
    }
}
