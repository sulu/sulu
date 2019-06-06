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
use Sulu\Bundle\PageBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageResourcelocatorController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * return resource-locator for sub-node.
     *
     * @throws MissingArgumentException
     *
     * @deprecated since 2.0, use ResourcelocatorController::postAction instead
     *
     * @return Response
     */
    public function postGenerateAction(Request $request)
    {
        $parentUuid = $this->getRequestParameter($request, 'parent');
        $parts = $this->getRequestParameter($request, 'parts', true);
        $templateKey = $this->getRequestParameter($request, 'template', true);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $locale = $this->getLocale($request);

        $result = $this->getResourceLocatorRepository()->generate(
            $parts,
            $parentUuid,
            $webspaceKey,
            $locale,
            $templateKey
        );

        return $this->handleView($this->view($result));
    }

    /**
     * return all resource locators for given node.
     *
     * @param string $id
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction($id, Request $request)
    {
        list($webspaceKey, $locale) = $this->getWebspaceAndLanguage($request);
        $result = $this->getResourceLocatorRepository()->getHistory($id, $webspaceKey, $locale);

        return $this->handleView($this->view($result));
    }

    /**
     * deletes resource locator with given path.
     *
     * @param string $id
     * @param Request $request
     *
     * @return Response
     */
    public function cdeleteAction($id, Request $request)
    {
        list($webspaceKey, $locale) = $this->getWebspaceAndLanguage($request);
        $path = $this->getRequestParameter($request, 'ids', true); // TODO rename path to id in all function names

        $this->getResourceLocatorRepository()->delete($path, $webspaceKey, $locale);
        $this->getDocumentManager()->flush();

        return $this->handleView($this->view());
    }

    /**
     * returns webspacekey and languagecode.
     *
     * @param Request $request
     *
     * @return array list($webspaceKey, $locale)
     */
    private function getWebspaceAndLanguage(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $locale = $this->getRequestParameter($request, 'locale', true);

        return [$webspaceKey, $locale];
    }

    /**
     * @return ResourceLocatorRepositoryInterface
     */
    private function getResourceLocatorRepository()
    {
        return $this->get('sulu_page.rl_repository');
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
        return $this->getRequestParameter($request, 'locale', true);
    }
}
