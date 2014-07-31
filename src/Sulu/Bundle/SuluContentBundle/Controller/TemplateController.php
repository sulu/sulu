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

use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles templates for this bundles
 */
class TemplateController extends Controller
{
    /**
     * returns all structures in system
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $internal = $request->get('internal', false);

        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->get('sulu.content.structure_manager');
        $structures = $structureManager->getStructures();

        $templates = array();
        foreach ($structures as $structure) {
            if (!$structure->getInternal() || $internal !== false) {
                $templates[] = array(
                    'internal' => $structure->getInternal(),
                    'template' => $structure->getKey()

                );
            }
        }

        $data = array(
            '_embedded' => $templates,
            'total' => sizeof($templates)
        );

        return new JsonResponse($data);
    }

    /**
     * renders one structure as form
     * @param Request $request
     * @param string $key template key
     * @return Response
     */
    public function contentAction(Request $request, $key = null)
    {
        $fireEvent = false;
        $templateIndex = null;
        if ($key === null) {
            $key = $this->container->getParameter('sulu.content.template.default');
            $fireEvent = true;
        }

        $webspace = $request->get('webspace');
        $language = $request->get('language');

        $template = $this->getTemplateStructure($key);

        return $this->render(
            'SuluContentBundle:Template:content.html.twig',
            array(
                'template' => $template,
                'wsUrl' => 'ws://' . $request->getHttpHost(),
                'wsPort' => $this->container->getParameter('sulu_content.preview.websocket.port'),
                'templateKey' => $key,
                'fireEvent' => $fireEvent,
                'webspaceKey' => $webspace,
                'languageCode' => $language
            )
        );
    }

    /**
     * returns form for seo tab
     * @return Response
     */
    public function seoAction()
    {
        return $this->render(
            'SuluContentBundle:Template:seo.html.twig'
        );
    }

    /**
     * returns form for seo tab
     * @return Response
     */
    public function excerptAction()
    {
        return $this->render(
            'SuluContentBundle:Template:excerpt.html.twig'
        );
    }

    /**
     * returns structure for given key
     * @param string $key template key
     * @return StructureInterface
     */
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

        return $this->render(
            'SuluContentBundle:Template:column.html.twig',
            array(
                'localizations' => $localizations,
                'currentLocalization' => $currentLocalization,
                'webspace' => $webspace
            )
        );
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
