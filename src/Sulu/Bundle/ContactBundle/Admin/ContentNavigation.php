<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\ContactBundle\Admin;

// TODO: this file does not belong here
/*
 * possible solutions:
 * 1. extend interface of sulu core bundle
 * 2. make this part of adminbundle -> contentnavigation
 */
class ContentNavigation
{

    private $navigation;

    public function __construct()
    {
        $this->navigation = array();
    }

    public function addNavigationItem($navigationItem)
    {
        $this->navigation[] = $navigationItem;
    }

    public function getNavigation()
    {
        return $this->navigation;
    }

    public function toArray()
    {
        $navigation = array();

        foreach ($this->navigation as $navigationItem) {
            $navigation[] = array(
                'title' => $navigationItem->getName(),
                'action' => $navigationItem->getUrl(),
            );
        }

        return $navigation;
    }
}