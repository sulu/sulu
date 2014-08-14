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

use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\UserSettings;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function indexAction()
    {
        // get user data
        $userDataServiceId = $this->container->getParameter('sulu_admin.user_data_service');
        $jsconfigServiceId = 'sulu_admin.jsconfig_pool';

        $user = array();
        if ($this->has($userDataServiceId)) {
            /** @var UserManagerInterface $userManager */
            $userManager = $this->get($userDataServiceId);
            if ($userManager->getCurrentUserData()->isLoggedIn()) {
                $user = $userManager->getCurrentUserData()->toArray();

                // get js config from bundles
                $jsconfig = array();
                if ($this->has($jsconfigServiceId)) {
                    $jsconfig = $this->get($jsconfigServiceId);
                    $jsconfig = $jsconfig->getConfigParams();
                }

                // render template
                if ($this->get('kernel')->getEnvironment() === 'dev') {
                    $template = 'SuluAdminBundle:Admin:index.html.twig';
                } else {
                    $template = 'SuluAdminBundle:Admin:index.html.dist.twig';
                }

                return $this->render(
                    $template,
                    array(
                        'name' => $this->container->getParameter('sulu_admin.name'),
                        'suluVersion' => $this->container->getParameter('sulu.version'),
                        'user' => $user,
                        'config' => $jsconfig
                    )
                );
            } else {
                return $this->redirect($this->generateUrl('sulu_admin.login'));
            }
        }
    }

    /**
     * Returns a array of all bundles
     * @return Response
     */
    public function bundlesAction()
    {
        /** @var AdminPool $pool */
        $pool = $this->get('sulu_admin.admin_pool');

        $admins = array();

        foreach ($pool->getAdmins() as $admin) {
            $name = $admin->getJsBundleName();
            if ($name !== null) {
                $admins[] = $name;
            }
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
