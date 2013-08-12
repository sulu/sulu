<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

/**
 * This controller returns the navigations for the TranslateBundle
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class NavigationsController extends FOSRestController
{
    /**
     * Returns the content navigation of the item with the given id
     * @param $id integer
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getNavigationContentsAction($id)
    {
        $catalogue = new NavigationItem('Catalogue');
        $catalogue->setHeaderTitle('Catalogue');

        $details = new NavigationItem('Details');
        $details->setAction('settings/translate/details:' . $id);
        $details->setType('content');
        $catalogue->addChild($details);

        $settings = new NavigationItem('Settings');
        $settings->setAction('settings/translate/settings:' . $id);
        $settings->setType('content');
        $catalogue->addChild($settings);

        $navigation = new Navigation($catalogue);

        $view = $this->view($navigation->toArray());

        return $this->handleView($view);
    }
}