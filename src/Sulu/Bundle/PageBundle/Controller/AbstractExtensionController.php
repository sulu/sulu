<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use PHPCR\ItemNotFoundException;
use Sulu\Bundle\PageBundle\Repository\NodeRepositoryInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractExtensionController extends RestController implements
    ClassResourceInterface,
    SecuredControllerInterface,
    SecuredObjectControllerInterface
{
    use RequestParametersTrait;

    abstract protected function getExtensionName();

    /**
     * returns webspace key from request.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getWebspace(Request $request)
    {
        return $this->getRequestParameter($request, 'webspace', true);
    }

    /**
     * @return NodeRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('sulu_page.node_repository');
    }

    public function cgetAction()
    {
        // only necessary for route generation in AdminController::configAction, therefore will always only return a 404
        throw new NotFoundHttpException();
    }

    public function getAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $webspace = $this->getWebspace($request);

        $view = $this->responseGetById(
            $id,
            function($id) use ($locale, $webspace) {
                try {
                    return $this->getRepository()->loadExtensionData(
                        $id,
                        $this->getExtensionName(),
                        $webspace,
                        $locale
                    );
                } catch (ItemNotFoundException $ex) {
                    return;
                }
            }
        );

        return $this->handleView($view);
    }

    public function putAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $webspace = $this->getWebspace($request);
        $data = $request->request->all();

        $this->getRepository()->saveExtensionData(
            $id,
            $data,
            $this->getExtensionName(),
            $webspace,
            $locale,
            $this->getUser()->getId()
        );

        $this->handleActionParameter($request->get('action'), $id, $locale);

        $result = $this->getRepository()->loadExtensionData(
            $id,
            $this->getExtensionName(),
            $webspace,
            $locale
        );

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return $this->getRequestParameter($request, 'locale', true);
    }

    public function getSecurityContext()
    {
        $requestAnalyzer = $this->get('sulu_core.webspace.request_analyzer');
        $webspace = $requestAnalyzer->getWebspace();

        if ($webspace) {
            return 'sulu.webspaces.' . $webspace->getKey();
        }
    }

    public function getSecuredClass()
    {
        return SecurityBehavior::class;
    }

    public function getSecuredObjectId(Request $request)
    {
        return $request->get('id');
    }

    private function handleActionParameter($actionParameter, $id, $locale)
    {
        $documentManager = $this->getDocumentManager();

        $document = $documentManager->find(
            $id,
            $locale,
            [
                'load_ghost_content' => false,
                'load_shadow_content' => false,
            ]
        );

        switch ($actionParameter) {
            case 'publish':
                $this->get('sulu_security.security_checker')->checkPermission(
                    new SecurityCondition(
                        $this->getSecurityContext(),
                        $locale,
                        $this->getSecuredClass(),
                        $id
                    ),
                    'live'
                );
                $documentManager->publish($document, $locale);
                break;
            default:
                $documentManager->persist($document, $locale);
                break;
        }

        $documentManager->flush();
    }

    protected function getDocumentManager(): DocumentManagerInterface
    {
        return $this->get('sulu_document_manager.document_manager');
    }
}
