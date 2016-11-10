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

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller provides an easy redirect to media-formats by redirecting.
 */
class MediaRedirectController extends Controller
{
    use RequestParametersTrait;

    /**
     * Redirects to format or original url.
     *
     * @param Request $request
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function redirectAction(Request $request, $id)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $format = $this->getRequestParameter($request, 'format');

        /** @var Media $media */
        $media = $this->container->get('sulu_media.media_manager')->getById($id, $locale);

        if (null === $format) {
            return $this->redirect($media->getUrl());
        }

        if (!array_key_exists($format, $media->getFormats())) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($media->getFormats()[$format]);
    }
}
