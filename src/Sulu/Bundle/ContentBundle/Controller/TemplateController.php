<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles templates for this bundles.
 */
class TemplateController extends Controller
{
    /**
     * Return the webspace manager.
     *
     * @return WebspaceManagerInterface
     */
    protected function getWebspaceManager()
    {
        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');

        return $webspaceManager;
    }

    /**
     * returns all structures in system.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $internal = $request->get('internal', false);

        $type = $request->get('type', Structure::TYPE_PAGE);

        if ($type === Structure::TYPE_PAGE) {
            $structureProvider = $this->get('sulu.content.webspace_structure_provider');
            $structures = $structureProvider->getStructures($request->get('webspace'));
        } else {
            $structureProvider = $this->get('sulu.content.structure_manager');
            $structures = $structureProvider->getStructures($type);
        }

        $templates = [];
        foreach ($structures as $structure) {
            if (false === $structure->getInternal() || $internal !== false) {
                $templates[] = [
                    'internal' => $structure->getInternal(),
                    'template' => $structure->getKey(),
                    'title' => $structure->getLocalizedTitle($this->getUser()->getLocale()),
                ];
            }
        }


        usort($templates, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        $data = [
            '_embedded' => $templates,
            'total' => count($templates),
        ];

        return new JsonResponse($data);
    }

    /**
     * renders one structure as form.
     *
     * @param Request $request
     * @param string  $key     template key
     *
     * @return Response
     */
    public function contentAction(Request $request, $key = null)
    {
        $fireEvent = false;
        $templateIndex = null;
        $webspace = $request->get('webspace');
        $language = $request->get('language');
        $uuid = $request->get('uuid');
        $type = $request->get('type', 'page');

        if ($key === null) {
            if ($type === 'page') {
                $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
                $key = $webspaceManager->findWebspaceByKey($webspace)->getDefaultTemplate($type);
                $fireEvent = true;
            } else {
                $defaultTypes = $this->container->getParameter('sulu.content.structure.default_types');
                $key = $defaultTypes[$type];
                $fireEvent = true;
            }
        }

        /** @var UserInterface $user */
        $user = $this->getUser();
        $userLocale = $user->getLocale();

        $template = $this->getTemplateStructure($key, $type);

        return $this->render(
            'SuluContentBundle:Template:content.html.twig',
            [
                'template' => $template,
                'webspaceKey' => $webspace,
                'languageCode' => $language,
                'uuid' => $uuid,
                'userLocale' => $userLocale,
                'templateKey' => $key,
                'fireEvent' => $fireEvent,
            ]
        );
    }

    /**
     * returns form for seo tab.
     *
     * @return Response
     */
    public function seoAction()
    {
        return $this->render(
            'SuluContentBundle:Template:seo.html.twig'
        );
    }

    /**
     * returns structure for given key.
     *
     * @param string $key  template key
     * @param string $type
     *
     * @return StructureInterface
     */
    private function getTemplateStructure($key, $type)
    {
        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->container->get('sulu.content.structure_manager');

        return $structureManager->getStructure($key, $type);
    }

    /**
     * renders list template.
     *
     * @return Response
     */
    public function listAction()
    {
        return $this->render('SuluContentBundle:Template:list.html.twig');
    }

    /**
     * renders column template.
     *
     * @param string $webspaceKey
     * @param string $languageCode
     *
     * @return Response
     */
    public function columnAction($webspaceKey, $languageCode)
    {
        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');
        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);
        $currentLocalization = $webspace->getLocalization($languageCode);
        $localizations = [];

        $i = 0;
        foreach ($webspace->getAllLocalizations() as $localization) {
            $localizations[] = [
                'localization' => $localization->getLocalization(),
                'name' => $localization->getLocalization('-'),
                'id' => $i++,
            ];
        }

        return $this->render(
            'SuluContentBundle:Template:column.html.twig',
            [
                'localizations' => $localizations,
                'currentLocalization' => $currentLocalization,
                'webspace' => $webspace,
            ]
        );
    }

    /**
     * renders template fpr settings.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return Response
     */
    public function settingsAction(Request $request)
    {
        $webspaceKey = $request->get('webspaceKey');
        $languageCode = $request->get('languageCode');
        $webspace = $this->getWebspaceManager()->findWebspaceByKey($webspaceKey);

        $navContexts = [];
        foreach ($webspace->getNavigation()->getContexts() as $context) {
            $navContexts[] = [
                'name' => $context->getTitle($languageCode),
                'id' => $context->getKey(),
            ];
        }

        $languages = [];
        foreach ($webspace->getAllLocalizations() as $localization) {
            $languages[] = $localization->getLocalization();
        }

        return $this->render(
            'SuluContentBundle:Template:settings.html.twig',
            [
                'languageCode' => $languageCode,
                'webspaceKey' => $webspaceKey,
                'navContexts' => $navContexts,
                'languages' => $languages,
            ]
        );
    }
}
