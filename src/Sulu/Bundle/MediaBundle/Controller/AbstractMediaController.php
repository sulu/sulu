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

use Sulu\Component\Rest\AbstractRestController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class contains all basic functions required in the various media controller classes.
 */
abstract class AbstractMediaController extends AbstractRestController
{
    /**
     * @param bool $fallback
     *
     * @return array
     */
    protected function getData(Request $request, $fallback = true)
    {
        $data = $request->request->all();
        $data['locale'] = $request->query->get('locale', $fallback ? $this->getLocale($request) : null);
        $data['collection'] = $request->query->get('collection') ?: $request->getPayload()->get('collection');

        return $data;
    }

    /**
     * @param string $name
     *
     * @return UploadedFile|null
     */
    protected function getUploadedFile(Request $request, $name)
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get($name);

        return $file;
    }
}
