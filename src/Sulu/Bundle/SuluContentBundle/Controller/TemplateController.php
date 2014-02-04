<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    public function contentAction($key)
    {
        $template = $this->getTemplateStructure($key);

        return $this->render(
            'SuluContentBundle:Template:content.html.twig',
            array(
                'template' => $template,
                'wsUrl' => 'ws://' . $this->getRequest()->getHttpHost(),
                'wsPort' => $this->container->getParameter('sulu_content.preview.websocket.port')
            )
        );
    }

    public function splitScreenAction($contentUuid)
    {
        return $this->render(
            'SuluContentBundle:Template:split-screen.html.twig',
            $this->getTemplateVarsSplitScreen($contentUuid)
        );
    }

    /**
     * FIXME remove after change ui (from new window to inline)
     * DEEP COPY from AdminController:indexAction
     * @param $contentUuid
     * @return array
     */
    private function getTemplateVarsSplitScreen($contentUuid)
    {
        // get user data
        $serviceId = $this->container->getParameter('sulu_admin.user_data_service');

        $user = array();
        if ($this->has($serviceId)) {
            /** @var UserManagerInterface $userManager */
            $userManager = $this->get($serviceId);
            $userData = $userManager->getCurrentUserData();

            // user settings
            $userSettings = $userData->getUserSettings();
            $first = true;
            $userSettingsString = '{';
            foreach ($userSettings as $settings) {
                if (!$first) {
                    $userSettingsString .= ',';
                } else {
                    $first = !$first;
                }
                $userSettingsString .= '"' . $settings->getKey() . '"' . ':' . $settings->getValue();
            }
            $userSettingsString .= '}';

            if ($userData->isLoggedIn()) {
                $user['id'] = $userData->getId();
                $user['username'] = $userData->getFullName();
                $user['logout'] = $userData->getLogoutLink();
                $user['locale'] = $userData->getLocale();
                $user['settings'] = $userSettingsString;
            }
        }

        // render template
        return array(
            'name' => $this->container->getParameter('sulu_admin.name'),
            'user' => $user,
            'contentUuid' => $contentUuid
        );
    }

    private function getTemplateStructure($key)
    {
        return $this->container->get('sulu.content.structure_manager')->getStructure($key);
    }

    public function listAction()
    {
        return $this->render('SuluContentBundle:Template:list.html.twig');
    }

    public function columnAction()
    {
        return $this->render('SuluContentBundle:Template:column.html.twig');
    }

}
