<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationInterface;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

/**
 *
 * @package Sulu\Bundle\AdminBundle\Admin
 */
abstract class ContentNavigation
{
    protected $id;
    protected $name;
    protected $header;
    protected $displayOption;
    protected $navigation;

    public function __construct($displayOption = null)
    {
        $this->navigation = array();

        // defaults
        if (is_null($displayOption)) {
            $this->displayOption = 'content';
        }
    }

    public function addNavigationItem($navigationItem)
    {
        $this->navigation[] = $navigationItem;
    }


    public function addNavigation(ContentNavigationInterface $navigation)
    {
        $this->navigation = array_merge(
            $this->navigation,
            $navigation->getNavigationItems()
        );
    }

    public function getNavigation()
    {
        return $this->navigation;
    }

    public function toArray($contentType = null)
    {

        $navigationItems = array();

        /** @var $navigationItem NavigationItem */
        foreach ($this->navigation as $navigationItem) {
//            if (null === $contentType || $navigationItem->getContentType() == $contentType) {
                $navigationItems[] = $navigationItem->toArray();
//            }
        }


        $navigation = array(
            'id'            => ($this->getId() != null) ? $this->getId() : uniqid(), //FIXME don't use uniqid()
            'title'         => $this->getName(),
            'header'        => $this->getHeader(),
            'items'           =>    $navigationItems
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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
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
