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

use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class contains all basic functions required in the various media controller classes.
 */
class AbstractMediaController extends RestController
{
    /**
     * Returns media-manager.
     *
     * @return MediaManagerInterface
     */
    protected function getMediaManager()
    {
        return $this->get('sulu_media.media_manager');
    }

    /**
     * Returns format-manager.
     *
     * @return FormatManagerInterface
     */
    protected function getFormatManager()
    {
        return $this->get('sulu_media.format_manager');
    }

    /**
     * @param Request $request
     * @param bool $fallback
     *
     * @return array
     */
    protected function getData(Request $request, $fallback = true)
    {
        $data = $request->request->all();
        $data['locale'] = $request->get('locale', $fallback ? $this->getLocale($request) : null);
        $data['collection'] = $request->get('collection');
        $data['contentLanguages'] = $request->get('contentLanguages', []);
        $data['publishLanguages'] = $request->get('publishLanguages', []);
        $data['title'] = $request->get('title', $fallback ? $this->getTitleFromUpload($request, 'fileVersion') : null);
        $data['formats'] = $request->get('formats', []);

        return $data;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getTitleFromUpload($request)
    {
        $uploadedFile = $this->getUploadedFile($request, 'fileVersion');

        if ($uploadedFile) {
            if (strpos($uploadedFile->getClientOriginalName(), '.') === false) {
                return $uploadedFile->getClientOriginalName();
            }

            return implode('.', explode('.', $uploadedFile->getClientOriginalName(), -1));
        }
    }

    /**
     * @param Request $request
     * @param $name
     *
     * @return UploadedFile
     */
    protected function getUploadedFile(Request $request, $name)
    {
        return $request->files->get($name);
    }
}
