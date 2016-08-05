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
use PHPCR\ItemNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Structure\SeoStructureExtension;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * handles sub resource for seo.
 */
class SeoController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

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
        return $this->get('sulu_content.node_repository');
    }

    /**
     * returns seo information for given node uuid.
     *
     * @param Request $request
     * @param string  $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, $uuid)
    {
        $language = $this->getLocale($request);
        $webspace = $this->getWebspace($request);

        $view = $this->responseGetById(
            $uuid,
            function ($id) use ($language, $webspace) {
                try {
                    return $this->getRepository()->loadExtensionData(
                        $id,
                        SeoStructureExtension::SEO_EXTENSION_NAME,
                        $webspace,
                        $language
                    );
                } catch (ItemNotFoundException $ex) {
                    return;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * handles a post request to save seo data.
     *
     * @param Request $request
     * @param string  $uuid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request, $uuid)
    {
        $language = $this->getLocale($request);
        $webspace = $this->getWebspace($request);
        $data = $request->request->all();

        $result = $this->getRepository()->saveExtensionData(
            $uuid,
            $data,
            SeoStructureExtension::SEO_EXTENSION_NAME,
            $webspace,
            $language,
            $this->getUser()->getId()
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
        return $this->getRequestParameter($request, 'language', true);
    }
}
