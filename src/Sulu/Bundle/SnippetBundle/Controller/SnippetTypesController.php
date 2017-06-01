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
            @trigger_error('Load default snippets over the snippet type is deprecated and will be removed in 2.0 use cgetDefaultAction instead', E_USER_DEPRECATED);

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
     * Get default types.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function cgetDefaultsAction(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::VIEW
        );

        $defaultSnippetManager = $this->get('sulu_snippet.default_snippet.manager');
        $defaultTypes = $this->getDefaultTypes();

        $defaults = [];
        foreach ($defaultTypes as $key => $defaultType) {
            $default = [
                'key' => $key,
                'template' => $defaultType['template'],
                'title' => $defaultType['title'],
            ];

            $snippet = $defaultSnippetManager->load($webspaceKey, $key, $this->getUser()->getLocale());
            $default['defaultUuid'] = $snippet ? $snippet->getUuid() : null;
            $default['defaultTitle'] = $snippet ? $snippet->getTitle() : null;

            $defaults[] = $default;
        }

        $data = [
            '_embedded' => [
                'defaults' => $defaults,
            ],
            'total' => count($defaults),
        ];

        return new JsonResponse($data);
    }

    /**
     * Save default snippet for given key.
     *
     * @param Request $request
     * @param string $key
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

        $defaultTypes = $this->getDefaultTypes();
        $defaultType = $defaultTypes[$key];

        $defaultSnippet = $this->get('sulu_snippet.default_snippet.manager')->save(
            $webspaceKey,
            $key,
            $default,
            $this->getUser()->getLocale()
        );

        return new JsonResponse(
            [
                'key' => $key,
                'template' => $defaultType['template'],
                'title' => $defaultType['title'],
                'defaultUuid' => $defaultSnippet ? $defaultSnippet->getUuid() : null,
                'defaultTitle' => $defaultSnippet ? $defaultSnippet->getTitle() : null,
            ]
        );
    }

    /**
     * Remove default snippet for given key.
     *
     * @param string $key
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteDefaultAction($key, Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::EDIT
        );

        $defaultTypes = $this->getDefaultTypes();
        $defaultType = $defaultTypes[$key];

        $this->get('sulu_snippet.default_snippet.manager')->remove($webspaceKey, $key);

        return new JsonResponse(
            [
                'key' => $key,
                'template' => $defaultType['template'],
                'title' => $defaultType['title'],
                'defaultUuid' => null,
                'defaultTitle' => null,
            ]
        );
    }

    /**
     * @return mixed
     */
    private function getDefaultTypes()
    {
        $defaultTypes = $this->getParameter('sulu_snippet.default_types');

        if (!empty($defaultTypes)) {
            return $defaultTypes;
        }

        @trigger_error('Use default snippets without defining them is deprecated and will be removed in 2.0', E_USER_DEPRECATED);

        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->get('sulu.content.structure_manager');
        $types = $structureManager->getStructures(Structure::TYPE_SNIPPET);

        foreach ($types as $type) {
            $defaultTypes[$type->getKey()] = [
                'title' => $type->getLocalizedTitle($this->getUser()->getLocale()),
                'template' => $type->getKey(),
            ];
        }

        return $defaultTypes;
    }
}
