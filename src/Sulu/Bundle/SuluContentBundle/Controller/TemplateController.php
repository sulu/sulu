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
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles templates for this bundles
 */
class TemplateController extends Controller
{
    /**
     * returns all structures in system
     * @return JsonResponse
     */
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

    /**
     * renders one structure as form
     * @param string $key template key
     * @return Response
     */
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

    /**
     * renders split screen
     * @deprecated todo remove completly
     *
     * @param $webspace
     * @param $language
     * @param $contentUuid
     * @return Response
     */
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

    /**
     * renders list template
     * @return Response
     */
    public function listAction()
    {
        return $this->render('SuluContentBundle:Template:list.html.twig');
    }

    /**
     * renders column template
     * @param string $webspaceKey
     * @param string $languageCode
     * @return Response
     */
    public function columnAction($webspaceKey, $languageCode)
    {
        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');
        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);
        $currentLocalization = $webspace->getLocalization($languageCode);
        $localizations = array();

        $i = 0;
        foreach ($webspace->getAllLocalizations() as $localization) {
            $localizations[] = array(
                'localization' => $localization->getLocalization(),
                'name' => $localization->getLocalization('-'),
                'id' => $i++
            );
        }

        return $this->render('SuluContentBundle:Template:column.html.twig', array(
                'localizations' => $localizations,
                'currentLocalization' => $currentLocalization,
                'webspace' => $webspace
            ));
    }

    /**
     * returns languages for webspaces
     * @param string $webspaceKey
     * @return JsonResponse
     */
    public function getLanguagesAction($webspaceKey)
    {
        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');
        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);
        $localizations = array();

        $i = 0;
        foreach ($webspace->getAllLocalizations() as $localization) {
            $localizations[] = array(
                'localization' => $localization->getLocalization(),
                'name' => $localization->getLocalization('-'),
                'id' => $i++
            );
        }

        $data = array(
            '_embedded' => $localizations,
            'total' => sizeof($localizations),
        );
        return new JsonResponse($data);
    }

    /**
     * renders template fpr settings
     * @return Response
     */
    public function settingsAction()
    {
        return $this->render('SuluContentBundle:Template:settings.html.twig');
    }

}
