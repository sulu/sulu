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

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles resource locator api.
 */
class NodeResourcelocatorController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * return resource-locator for sub-node.
     *
     * @throws MissingArgumentException
     *
     * @return Response
     */
    public function postGenerateAction(Request $request)
    {
        $parentUuid = $this->getRequestParameter($request, 'parent');
        $parts = $this->getRequestParameter($request, 'parts', true);
        $templateKey = $this->getRequestParameter($request, 'template', true);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $languageCode = $this->getLocale($request);

        $result = $this->getResourceLocatorRepository()->generate(
            $parts,
            $parentUuid,
            $webspaceKey,
            $languageCode,
            $templateKey
        );

        return $this->handleView($this->view($result));
    }

    /**
     * return all resource locators for given node.
     *
     * @param string $uuid
     *
     * @return Response
     */
    public function cgetAction($uuid)
    {
        list($webspaceKey, $languageCode) = $this->getWebspaceAndLanguage();
        $result = $this->getResourceLocatorRepository()->getHistory($uuid, $webspaceKey, $languageCode);

        return $this->handleView($this->view($result));
    }

    /**
     * deletes resource locator with given path.
     *
     * @return Response
     */
    public function deleteAction()
    {
        list($webspaceKey, $languageCode) = $this->getWebspaceAndLanguage();
        $path = $this->getRequestParameter($this->getRequest(), 'path', true);

        $this->getResourceLocatorRepository()->delete($path, $webspaceKey, $languageCode);
        $this->getDocumentManager()->flush();

        return $this->handleView($this->view());
    }

    /**
     * returns webspacekey and languagecode.
     *
     * @return array list($webspaceKey, $languageCode)
     */
    private function getWebspaceAndLanguage()
    {
        $webspaceKey = $this->getRequestParameter($this->getRequest(), 'webspace', true);
        $languageCode = $this->getRequestParameter($this->getRequest(), 'language', true);

        return [$webspaceKey, $languageCode];
    }

    /**
     * @return ResourceLocatorRepositoryInterface
     */
    private function getResourceLocatorRepository()
    {
        return $this->get('sulu_content.rl_repository');
    }

    /**
     * @return DocumentManagerInterface
     */
    private function getDocumentManager()
    {
        return $this->get('sulu_document_manager.document_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(Request $request)
    {
        return $this->getRequestParameter($request, 'language', true);
    }
}
