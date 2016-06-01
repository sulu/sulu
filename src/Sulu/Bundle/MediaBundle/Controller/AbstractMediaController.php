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
        return [
            'id' => $request->get('id'),
            'locale' => $request->get('locale', $fallback ? $this->getLocale($request) : null),
            'type' => $request->get('type'),
            'collection' => $request->get('collection'),
            'versions' => $request->get('versions'),
            'version' => $request->get('version'),
            'size' => $request->get('size'),
            'contentLanguages' => $request->get('contentLanguages', []),
            'publishLanguages' => $request->get('publishLanguages', []),
            'tags' => $request->get('tags'),
            'formats' => $request->get('formats', []),
            'url' => $request->get('url'),
            'name' => $request->get('name'),
            'title' => $request->get('title', $fallback ? $this->getTitleFromUpload($request, 'fileVersion') : null),
            'description' => $request->get('description'),
            'copyright' => $request->get('copyright'),
            'credits' => $request->get('credits'),
            'changer' => $request->get('changer'),
            'creator' => $request->get('creator'),
            'changed' => $request->get('changed'),
            'created' => $request->get('created'),
            'categories' => $request->get('categories'),
        ];
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getTitleFromUpload($request)
    {
        $title = null;

        $uploadedFile = $this->getUploadedFile($request, 'fileVersion');

        if ($uploadedFile) {
            $title = implode('.', explode('.', $uploadedFile->getClientOriginalName(), -1));
        }

        return $title;
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
