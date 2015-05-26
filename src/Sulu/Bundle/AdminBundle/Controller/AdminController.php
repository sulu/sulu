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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends Controller
{
    /**
     * ID of user data sevice.
     */
    const USER_DATA_ID = 'sulu_admin.user_data_service';

    /**
     * ID of js config service.
     */
    const JS_CONFIG_ID = 'sulu_admin.jsconfig_pool';

    /**
     * Renders admin ui.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        // get user data
        $userDataServiceId = $this->container->getParameter(self::USER_DATA_ID);

        $user = array();
        if ($this->has($userDataServiceId)) {
            /** @var UserManagerInterface $userManager */
            $userManager = $this->get($userDataServiceId);
            if ($userManager->getCurrentUserData()->isLoggedIn()) {
                $user = $userManager->getCurrentUserData()->toArray();

                // get js config from bundles
                $jsconfig = array();
                if ($this->has(self::JS_CONFIG_ID)) {
                    $jsconfig = $this->get(self::JS_CONFIG_ID);
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
                        'locales' => $this->container->getParameter('sulu_core.locales'),
                        'suluVersion' => $this->container->getParameter('sulu.version'),
                        'user' => $user,
                        'config' => $jsconfig,
                    )
                );
            } else {
                return $this->redirect($this->generateUrl('sulu_admin.login'));
            }
        }
    }

    /**
     * Returns a array of all bundles.
     *
     * @return JsonResponse
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

        return new JsonResponse($admins);
    }

    /**
     * Returns contexts of admin.
     *
     * @return JsonResponse
     */
    public function contextsAction()
    {
        $contexts = $this->get('sulu_admin.admin_pool')->getSecurityContexts();
        $system = $this->getRequest()->get('system');

        $response = isset($system) ? $contexts[$system] : $contexts;

        return new JsonResponse($response);
    }

    /**
     * Returns config for admin.
     *
     * @return JsonResponse
     */
    public function configAction()
    {
        // get js config from bundles
        $jsconfig = array();
        if ($this->has(self::JS_CONFIG_ID)) {
            $jsconfig = $this->get(self::JS_CONFIG_ID);
            $jsconfig = $jsconfig->getConfigParams();
        }

        return new JsonResponse($jsconfig);
    }
}
