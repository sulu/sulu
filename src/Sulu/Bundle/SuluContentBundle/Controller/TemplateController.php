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
use Sulu\Component\Content\StructureManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends Controller
{
    public function getAction()
    {
        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->get('sulu.content.structure_manager');
        $templates = $structureManager->getStructures();
        $data = array(
            '_embedded' => $templates,
            'total' => sizeof($templates),
        );
        return new JsonResponse($data);
    }

    public function contentAction($key = null)
    {
        $fireEvent = false;
        $templateIndex = null;
        if ($key === null) {
            $key = $this->container->getParameter('sulu.content.template.default');
            $fireEvent = true;
        }

        $webspace = $this->getRequest()->get('webspace');
        $language = $this->getRequest()->get('language');

        $template = $this->getTemplateStructure($key);

        return $this->render(
            'SuluContentBundle:Template:content.html.twig',
            array(
                'template' => $template,
                'wsUrl' => 'ws://' . $this->getRequest()->getHttpHost(),
                'wsPort' => $this->container->getParameter('sulu_content.preview.websocket.port'),
                'templateKey' => $key,
                'fireEvent' => $fireEvent,
                'webspaceKey' => $webspace,
                'languageCode' => $language
            )
        );
    }

    public function splitScreenAction($webspace, $language, $contentUuid)
    {
        return $this->render(
            'SuluContentBundle:Template:split-screen.html.twig',
            array_merge(
                $this->getTemplateVarsSplitScreen($contentUuid),
                array(
                    'webspace'=> $webspace,
                    'language' => $language
                )
            )
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
            $user = $userManager->getCurrentUserData()->toArray();
        }

        // render template
        return array(
            'name' => $this->container->getParameter('sulu_admin.name'),
            'user' => $user,
            'contentUuid' => $contentUuid,
            'url' => 'http://' . $this->getRequest()->getHttpHost()
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

    public function settingsAction()
    {
        return $this->render('SuluContentBundle:Template:settings.html.twig');
    }

}
