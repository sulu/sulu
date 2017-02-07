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
     * Save default snippet for given key.
     *
     * @param string $key
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function putDefaultAction($key, Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->get('sulu_security.security_checker')->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::EDIT
        );

        $default = $request->get('default');

        $type = $this->get('sulu.content.structure_manager')->getStructure($key, Structure::TYPE_SNIPPET);
        $defaultSnippet = $this->get('sulu_snippet.default_snippet.manager')->save(
            $webspaceKey,
            $key,
            $default,
            $this->getUser()->getLocale()
        );

        return new JsonResponse(
            [
                'template' => $type->getKey(),
                'title' => $type->getLocalizedTitle($this->getUser()->getLocale()),
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

        $type = $this->get('sulu.content.structure_manager')->getStructure($key, Structure::TYPE_SNIPPET);
        $this->get('sulu_snippet.default_snippet.manager')->remove($webspaceKey, $key);

        return new JsonResponse(
            [
                'template' => $type->getKey(),
                'title' => $type->getLocalizedTitle($this->getUser()->getLocale()),
                'defaultUuid' => null,
                'defaultTitle' => null,
            ]
        );
    }
}
