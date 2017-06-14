<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles snippet types and defaults.
 *
 * @RouteResource("snippet-types")
 */
class SnippetTypesController extends Controller implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * Returns all snippet types.
     *
     * @return JsonResponse
     */
    public function cgetAction(Request $request)
    {
        $defaults = $this->getBooleanRequestParameter($request, 'defaults');
        $webspaceKey = $this->getRequestParameter($request, 'webspace', $defaults);
        if ($defaults) {
            @trigger_error('Load default snippets over the cgetAction is deprecated and will be removed in 2.0 use cgetAreasAction instead', E_USER_DEPRECATED);

            $this->get('sulu_security.security_checker')->checkPermission(
                new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
                PermissionTypes::VIEW
            );
        }

        $defaultSnippetManager = $this->get('sulu_snippet.default_snippet.manager');

        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->get('sulu.content.structure_manager');
        $types = $structureManager->getStructures(Structure::TYPE_SNIPPET);

        $templates = [];
        foreach ($types as $type) {
            $template = [
                'template' => $type->getKey(),
                'title' => $type->getLocalizedTitle($this->getUser()->getLocale()),
            ];

            if ($defaults) {
                $default = $defaultSnippetManager->load($webspaceKey, $type->getKey(), $this->getUser()->getLocale());

                $template['defaultUuid'] = $default ? $default->getUuid() : null;
                $template['defaultTitle'] = $default ? $default->getTitle() : null;
            }

            $templates[] = $template;
        }

        $data = [
            '_embedded' => $templates,
            'total' => count($templates),
        ];

        return new JsonResponse($data);
    }

    /**
     * Get snippet areas.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cgetAreasAction(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::VIEW
        );

        $defaultSnippetManager = $this->get('sulu_snippet.default_snippet.manager');
        $areas = $this->getLocalizedAreas();

        $dataList = [];
        foreach ($areas as $key => $area) {
            $areaData = [
                'key' => $key,
                'template' => $area['template'],
                'title' => $area['title'],
            ];

            $snippet = $defaultSnippetManager->load($webspaceKey, $key, $this->getUser()->getLocale());
            $areaData['defaultUuid'] = $snippet ? $snippet->getUuid() : null;
            $areaData['defaultTitle'] = $snippet ? $snippet->getTitle() : null;

            $dataList[] = $areaData;
        }

        $data = [
            '_embedded' => [
                'areas' => $dataList,
            ],
            'total' => count($dataList),
        ];

        return new JsonResponse($data);
    }

    /**
     * Put default action.
     *
     * @param Request $request
     * @param $key
     *
     * @return JsonResponse
     */
    public function putDefaultAction(Request $request, $key)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::EDIT
        );

        $default = $request->get('default');

        $areas = $this->getLocalizedAreas();
        $area = $areas[$key];

        $defaultSnippet = $this->get('sulu_snippet.default_snippet.manager')->save(
            $webspaceKey,
            $key,
            $default,
            $this->getUser()->getLocale()
        );

        return new JsonResponse(
            [
                'key' => $key,
                'template' => $area['template'],
                'title' => $area['title'],
                'defaultUuid' => $defaultSnippet ? $defaultSnippet->getUuid() : null,
                'defaultTitle' => $defaultSnippet ? $defaultSnippet->getTitle() : null,
            ]
        );
    }

    /**
     * Delete default action.
     *
     * @param Request $request
     * @param $key
     *
     * @return JsonResponse
     */
    public function deleteDefaultAction(Request $request, $key)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::EDIT
        );

        $areas = $this->getLocalizedAreas();
        $area = $areas[$key];

        $this->get('sulu_snippet.default_snippet.manager')->remove($webspaceKey, $key);

        return new JsonResponse(
            [
                'key' => $key,
                'template' => $area['template'],
                'title' => $area['title'],
                'defaultUuid' => null,
                'defaultTitle' => null,
            ]
        );
    }

    /**
     * Get snippet default types.
     *
     * @return array
     */
    private function getLocalizedAreas()
    {
        $areas = $this->getParameter('sulu_snippet.areas');
        $locale = $this->getUser()->getLocale();

        $localizedAreas = [];

        foreach ($areas as $type) {
            $title = $type['key'];

            if (isset($type['title'][$locale])) {
                $title = $type['title'][$locale];
            }

            $localizedAreas[$type['key']] = [
                'title' => $title,
                'template' => $type['template'],
            ];
        }

        return $localizedAreas;
    }
}
