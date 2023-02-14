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

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This controller provides an easy redirect to media-formats by redirecting.
 */
class MediaRedirectController
{
    use RequestParametersTrait;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    public function __construct(
        MediaManagerInterface $mediaManager
    ) {
        $this->mediaManager = $mediaManager;
    }

    /**
     * Redirects to format or original url.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function redirectAction(Request $request, $id)
    {
        $locale = $this->getRequestParameter($request, 'locale', true);
        $format = $this->getRequestParameter($request, 'format');

        try {
            /** @var Media $media */
            $media = $this->mediaManager->getById($id, $locale);
        } catch (MediaNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        if (null === $format) {
            return new RedirectResponse($media->getUrl());
        }

        if (!\array_key_exists($format, $media->getFormats())) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($media->getFormats()[$format]);
    }
}
