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

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationInterface;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

/**
 * This class ontains the content navigation represented as tabs in the forms from the admin
 * @package Sulu\Bundle\AdminBundle\Admin
 */
abstract class ContentNavigation
{
    protected $id;

    protected $name;

    protected $header;

    protected $displayOption;

    /**
     * @var ContentNavigationItem[]
     */
    protected $navigationItems;

    public function __construct($displayOption = null)
    {
        $this->navigationItems = array();

        // defaults
        if (is_null($displayOption)) {
            $this->displayOption = 'content';
        }
    }

    /**
     * Adds a navigation item to the content navigation
     * @param ContentNavigationItem $navigationItem
     */
    public function addNavigationItem(ContentNavigationItem $navigationItem)
    {
        $this->navigationItems[] = $navigationItem;
    }


    public function addNavigation(ContentNavigationInterface $navigation)
    {
        $this->navigationItems = array_merge(
            $this->navigationItems,
            $navigation->getNavigationItems()
        );
    }

    /**
     * Returns all the content navigation items
     * @return ContentNavigationItem[]
     */
    public function getNavigationItems()
    {
        return $this->navigationItems;
    }

    public function toArray($contentType = null)
    {
        $navigationItems = array();

        foreach ($this->navigationItems as $navigationItem) {
            if (null === $contentType || in_array($contentType, $navigationItem->getGroups())) {
                $navigationItems[] = $navigationItem->toArray();
            }
        }

        $navigation = array(
            'id'            => ($this->getId() != null) ? $this->getId() : uniqid(), //FIXME don't use uniqid()
            'title'         => $this->getName(),
            'header'        => $this->getHeader(),
            'displayOption' => $this->getDisplayOption(),
            'items'         => $navigationItems
        );

        return $navigation;
    }

    /**
     * @param string $displayOption
     */
    public function setDisplayOption($displayOption)
    {
        $this->displayOption = $displayOption;
    }

    /**
     * @return string
     */
    public function getDisplayOption()
    {
        return $this->displayOption;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
