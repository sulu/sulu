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

use Sulu\Bundle\AdminBundle\UserData\UserDataInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class AdminController extends Controller
{
    public function indexAction()
    {
        // get user data
        $serviceId = $this->container->getParameter('sulu_admin.user_data_service');
        $user = array();
        if ($this->has($serviceId)) {
            /** @var UserDataInterface $userData */
            $userData = $this->get($serviceId);
            if ($userData->isLoggedIn() && $userData->isAdminUser()) {
                $user['username'] = $userData->getUserName();
                $user['logout'] = $userData->getLogoutLink();
            }
        }

        // render template
        return $this->render(
            'SuluAdminBundle:Admin:index.html.twig',
            array(
                'name' => $this->container->getParameter('sulu_admin.name'),
                'user' => $user
            )
        );
    }

    /**
     * Returns a array of all bundles
     * @return Response
     */
    public function bundlesAction()
    {
        $pool = $this->get('sulu_admin.admin_pool');

        $admins = array();

        foreach ($pool->getAdmins() as $admin) {
            $reflection = new \ReflectionClass($admin);
            $name = strtolower(str_replace('Admin', '', $reflection->getShortName()));
            $admins[] = $name;
        }

        $response = json_encode($admins);

        return new Response($response);
    }

    public function contextsAction()
    {
        $contexts = $this->get('sulu_admin.admin_pool')->getSecurityContexts();
        $system = $this->getRequest()->get('system');

        $response = json_encode((isset($system) ? $contexts[$system] : $contexts));

        return new Response($response);
    }
}
