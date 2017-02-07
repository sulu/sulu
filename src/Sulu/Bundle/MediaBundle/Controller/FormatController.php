<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\FormatOptions\FormatOptionsManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the image formats, with the format options available through the REST API.
 */
class FormatController extends RestController implements ClassResourceInterface
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
        $formats = $this->getFormatManager()->getFormatDefinitions($locale, $formatOptions);

        return $this->handleView($this->view($formats));
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
        $options = $request->get('options', []);
        $locale = $this->getRequestParameter($request, 'locale', true);

        if (empty($options)) {
            $this->getFormatOptionsManager()->delete($id, $key);
        } else {
            $this->getFormatOptionsManager()->save($id, $key, $options);
        }
        $this->get('doctrine.orm.entity_manager')->flush();

        $formatOptions = $this->getFormatOptionsManager()->get($id, $key);
        $format = $this->getFormatManager()->getFormatDefinition($key, $locale, $formatOptions);

        return $this->handleView($this->view($format));
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
