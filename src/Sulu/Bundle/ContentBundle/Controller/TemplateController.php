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

use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
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

        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->get('sulu.content.structure_manager');
        $structures = $structureManager->getStructures();

        $templates = array();
        foreach ($structures as $structure) {
            if (!$structure->getInternal() || $internal !== false) {
                $templates[] = array(
                    'internal' => $structure->getInternal(),
                    'template' => $structure->getKey(),
                    'title' => $structure->getLocalizedTitle($this->getUser()->getLocale()),
                );
            }
        }

        $data = array(
            '_embedded' => $templates,
            'total' => sizeof($templates),
        );

        return new JsonResponse($data);
    }

    /**
     * renders one structure as form.
     *
     * @param Request $request
     * @param string $key template key
     *
     * @return Response
     */
    public function contentAction(Request $request, $key = null)
    {
        $fireEvent = false;
        $templateIndex = null;
        if ($key === null) {
            $key = $this->container->getParameter('sulu.content.structure.default_type.page');
            $fireEvent = true;
        }

        $webspace = $request->get('webspace');
        $language = $request->get('language');
        $type = $request->get('type', 'page');

        /** @var UserInterface $user */
        $user = $this->getUser();
        $userLocale = $user->getLocale();

        $template = $this->getTemplateStructure($key, $type);

        return $this->render(
            'SuluContentBundle:Template:content.html.twig',
            array(
                'template' => $template,
                'webspaceKey' => $webspace,
                'languageCode' => $language,
                'userLocale' => $userLocale,
                'templateKey' => $key,
                'fireEvent' => $fireEvent,
            )
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
     * returns form for seo tab.
     *
     * @return Response
     */
    public function excerptAction()
    {
        return $this->render(
            'SuluContentBundle:Template:excerpt.html.twig'
        );
    }

    /**
     * returns structure for given key.
     *
     * @param string $key template key
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
        $localizations = array();

        $i = 0;
        foreach ($webspace->getAllLocalizations() as $localization) {
            $localizations[] = array(
                'localization' => $localization->getLocalization(),
                'name' => $localization->getLocalization('-'),
                'id' => $i++,
            );
        }

        return $this->render(
            'SuluContentBundle:Template:column.html.twig',
            array(
                'localizations' => $localizations,
                'currentLocalization' => $currentLocalization,
                'webspace' => $webspace,
            )
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

        $navContexts = array();
        foreach ($webspace->getNavigation()->getContexts() as $context) {
            $navContexts[] = array(
                'name' => $context->getTitle($languageCode),
                'id' => $context->getKey(),
            );
        }

        $languages = array();
        foreach ($webspace->getAllLocalizations() as $localization) {
            $languages[] = $localization->getLocalization();
        }

        return $this->render(
            'SuluContentBundle:Template:settings.html.twig',
            array(
                'languageCode' => $languageCode,
                'webspaceKey' => $webspaceKey,
                'navContexts' => $navContexts,
                'languages' => $languages,
            )
        );
    }
}
