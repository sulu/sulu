<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
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
        $response = '';

        $pool = $this->get('sulu_admin.admin_pool');

        foreach ($pool->getAdmins() as $admin) {
            $reflection = new \ReflectionClass($admin);
            $name = strtolower(str_replace('Admin', '', $reflection->getShortName()));
            $response .= 'require([\'/bundles/' . $name . '/js/main.js\']);';
        }

        return new Response($response);
    }
}