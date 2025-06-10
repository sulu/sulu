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

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\PageBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageResourcelocatorController extends AbstractRestController
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private ResourceLocatorRepositoryInterface $resourceLocatorRepository,
        private DocumentManagerInterface $documentManager
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * return resource-locator for sub-node.
     *
     * @return Response
     *
     * @throws MissingArgumentException
     *
     * @deprecated since 2.0, use ResourcelocatorController::postAction instead
     */
    public function postGenerateAction(Request $request)
    {
        $parentUuid = $this->getRequestParameter($request, 'parent');
        $parts = $this->getRequestParameter($request, 'parts', true);
        $templateKey = $this->getRequestParameter($request, 'template', true);
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $locale = $this->getLocale($request);

        $result = $this->resourceLocatorRepository->generate(
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
     *
     * @return Response
     */
    public function cgetAction($id, Request $request)
    {
        list($webspaceKey, $locale) = $this->getWebspaceAndLanguage($request);
        $result = $this->resourceLocatorRepository->getHistory($id, $webspaceKey, $locale);

        return $this->handleView($this->view($result));
    }

    /**
     * deletes resource locator with given path.
     *
     * @param string $id
     *
     * @return Response
     */
    public function cdeleteAction($id, Request $request)
    {
        list($webspaceKey, $locale) = $this->getWebspaceAndLanguage($request);
        $path = $this->getRequestParameter($request, 'ids', true); // TODO rename path to id in all function names

        $this->resourceLocatorRepository->delete($path, $webspaceKey, $locale);
        $this->documentManager->flush();

        return $this->handleView($this->view());
    }

    /**
     * returns webspacekey and languagecode.
     *
     * @return array list($webspaceKey, $locale)
     */
    private function getWebspaceAndLanguage(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $locale = $this->getRequestParameter($request, 'locale', true);

        return [$webspaceKey, $locale];
    }

    public function getLocale(Request $request)
    {
        return $this->getRequestParameter($request, 'locale', true);
    }
}
