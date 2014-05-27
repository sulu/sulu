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

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepositoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;

/**
 * handles resource locator api
 */
class ResourcelocatorController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * return resource-locator for sub-node
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postGenerateAction()
    {
        $parentUuid = $this->getRequestParameter($this->getRequest(), 'parent');
        $uuid = $this->getRequestParameter($this->getRequest(), 'uuid');
        $parts = $this->getRequestParameter($this->getRequest(), 'parts', true);
        $webspaceKey = $this->getRequestParameter($this->getRequest(), 'webspace', true);
        $languageCode = $this->getRequestParameter($this->getRequest(), 'language', true);
        $templateKey = $this->getRequestParameter($this->getRequest(), 'template', true);

        $result = $this->getResourceLocatorRepository()->generate($parts, $parentUuid, $uuid, $webspaceKey, $languageCode, $templateKey);

        return $this->handleView($this->view($result));
    }

    /**
     * @return ResourceLocatorRepositoryInterface
     */
    private function getResourceLocatorRepository()
    {
        return $this->get('sulu_content.rl_repository');
    }
}
