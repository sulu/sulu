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

class ContentNavigationItem
{
    const DISPLAY_NEW = 'new';

    const DISPLAY_EDIT = 'edit';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $action;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var string
     */
    private $component;

    /**
     * @var array
     */
    private $componentOptions;

    /**
     * @var array
     */
    private $display;

    /**
     * @param $name
     */
    function __construct($name)
    {
        $this->name = $name;
        $this->display = array(static::DISPLAY_NEW, static::DISPLAY_EDIT);
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
     * Returns an array representation of the content navigation item
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'title' => $this->getName(),
            'action' => $this->getAction(),
            'display' => $this->getDisplay(),
            'component' => $this->getComponent(),
            'componentOptions' => $this->getComponentOptions(),
        );

        return $array;
    }
}
