<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Format")
 */
class MediaFormatController extends RestController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * Returns all format resources.
     *
     * @param $id
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction($id, Request $request)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $formatOptions = $this->getFormatOptionsManager()->getAll($id);

        return $this->handleView($this->view(count($formatOptions) > 0 ? $formatOptions : new \stdClass()));
    }

    /**
     * Edits a format resource.
     *
     * @param int $id
     * @param string $key
     * @param Request $request
     *
     * @return Response
     */
    public function putAction($id, $key, Request $request)
    {
        $options = $request->request->all();
        $locale = $this->getRequestParameter($request, 'locale', true);

        if (empty($options)) {
            $this->getFormatOptionsManager()->delete($id, $key);
        } else {
            $this->getFormatOptionsManager()->save($id, $key, $options);
        }
        $this->get('doctrine.orm.entity_manager')->flush();

        $formatOptions = $this->getFormatOptionsManager()->get($id, $key);

        return $this->handleView($this->view($formatOptions));
    }

    /**
     * @return FormatManagerInterface
     */
    private function getFormatManager()
    {
        return $this->get('sulu_media.format_manager');
    }

    /**
     * @return FormatOptionsManagerInterface
     */
    private function getFormatOptionsManager()
    {
        return $this->get('sulu_media.format_options_manager');
    }
}
