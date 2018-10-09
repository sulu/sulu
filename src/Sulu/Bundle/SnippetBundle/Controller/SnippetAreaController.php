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
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles snippet types and defaults.
 *
 * @RouteResource("snippet-area")
 */
class SnippetAreaController extends Controller implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * Get snippet areas.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cgetAction(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::VIEW
        );

        $defaultSnippetManager = $this->get('sulu_snippet.default_snippet.manager');
        $documentManager = $this->get('sulu_document_manager.document_manager');
        $areas = $this->getLocalizedAreas();

        $dataList = [];
        foreach ($areas as $key => $area) {
            $areaData = [
                'key' => $key,
                'template' => $area['template'],
                'title' => $area['title'],
                'defaultUuid' => null,
                'defaultTitle' => null,
                'valid' => true,
            ];

            try {
                $snippet = $defaultSnippetManager->load($webspaceKey, $key, $this->getUser()->getLocale());
                $areaData['defaultUuid'] = $snippet ? $snippet->getUuid() : null;
                $areaData['defaultTitle'] = $snippet ? $snippet->getTitle() : null;
            } catch (WrongSnippetTypeException $exception) {
                // ignore wrong snippet-type
                $areaData['valid'] = false;

                $uuid = $defaultSnippetManager->loadIdentifier($webspaceKey, $key);
                $snippet = $documentManager->find($uuid, $this->getUser()->getLocale());
                $areaData['defaultUuid'] = $snippet ? $snippet->getUuid() : null;
                $areaData['defaultTitle'] = $snippet ? $snippet->getTitle() : null;
            }

            $dataList[$key] = $areaData;
        }

        ksort($dataList);

        $data = [
            '_embedded' => [
                'areas' => array_values($dataList),
            ],
            'total' => count($dataList),
        ];

        return new JsonResponse($data);
    }

    /**
     * Put default action.
     *
     * @param Request $request
     * @param string $key
     *
     * @return JsonResponse
     */
    public function putAction(Request $request, $key)
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
                'valid' => true,
            ]
        );
    }

    /**
     * Delete default action.
     *
     * @param Request $request
     * @param string $key
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $key)
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
                'valid' => true,
            ]
        );
    }

    /**
     * Get snippet areas.
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
