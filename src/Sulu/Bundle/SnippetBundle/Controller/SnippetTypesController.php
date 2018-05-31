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
            @trigger_error('Load default snippets over the cgetAction is deprecated and will be removed in 2.0 use SnippetAreaController::cgetAction instead', E_USER_DEPRECATED);

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

            $templates[$type->getKey()] = $template;
        }

        ksort($templates);

        $data = [
            '_embedded' => array_values($templates),
            'total' => count($templates),
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
    public function putDefaultAction(Request $request, $key)
    {
        @trigger_error('Set default snippets over the putDefaultAction is deprecated and will be removed in 2.0 use SnippetAreaController::putAction instead', E_USER_DEPRECATED);

        return $this->forward('SuluSnippetBundle:SnippetArea:put', $request->attributes->all(), $request->query->all());
    }

    /**
     * Delete default action.
     *
     * @param Request $request
     * @param string $key
     *
     * @return JsonResponse
     */
    public function deleteDefaultAction(Request $request, $key)
    {
        @trigger_error('Remove default snippets over the deleteDefaultAction is deprecated and will be removed in 2.0 use SnippetAreaController::deleteAction instead', E_USER_DEPRECATED);

        return $this->forward('SuluSnippetBundle:SnippetArea:delete', $request->attributes->all(), $request->query->all());
    }
}
