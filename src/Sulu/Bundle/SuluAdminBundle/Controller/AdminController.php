<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class AdminController extends Controller
{
    /**
     * Create the javascript which sets the routes for each bundle
     * @return Response
     */
    public function routesAction()
    {
        $requires = array();

        $pool = $this->get('sulu_admin.admin_pool');

        foreach ($pool->getAdmins() as $admin) {
            $reflection = new \ReflectionClass($admin);
            $name = strtolower(str_replace('Admin', '', $reflection->getShortName()));
            $requires[] = '\'/bundles/' . $name . '/js/main.js\'';
        }

        $response = 'require(['.implode(', ', $requires).'], function() {
            Backbone.history.start();
        })';

        return new Response($response);
    }
}