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

use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles preview with ajax calls and renders basic preview.
 */
class PreviewController extends Controller
{
    /**
     * id of preview service.
     */
    const PREVIEW_ID = 'sulu_content.preview';

    use RequestParametersTrait;

    /**
     * render content for logged in user with data from FORM.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $contentUuid
     *
     * @throws \Exception
     * @throws \Sulu\Bundle\ContentBundle\Preview\PreviewNotFoundException
     *
     * @return Response
     */
    public function renderAction(Request $request, $contentUuid)
    {
        $request->request->set('preview', true);

        $uid = $this->getUserId();
        $preview = $this->getPreview();

        $webspaceKey = $this->getWebspaceKey($request);
        $locale = $this->getLanguageCode($request);

        if (!$preview->started($uid, $contentUuid, $webspaceKey, $locale)) {
            $preview->start($uid, $contentUuid, $webspaceKey, $locale);
        }

        $content = $preview->render($uid, $contentUuid, $webspaceKey, $locale);

        return new Response($content);
    }

    /**
     * @return PreviewInterface
     */
    private function getPreview()
    {
        return $this->get(self::PREVIEW_ID);
    }

    /**
     * @return int
     */
    private function getUserId()
    {
        return $this->getUser()->getId();
    }

    /**
     * returns language code from request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return string
     */
    private function getLanguageCode(Request $request)
    {
        return $this->getRequestParameter($request, 'language', true);
    }

    /**
     * returns webspace key from request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return string
     */
    private function getWebspaceKey(Request $request)
    {
        return $this->getRequestParameter($request, 'webspace', true);
    }
}
