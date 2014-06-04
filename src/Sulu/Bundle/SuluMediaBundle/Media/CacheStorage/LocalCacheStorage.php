<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\CacheStorage;


use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyUrlNotFoundException;

class LocalCacheStorage implements CacheStorageInterface {

    /**
     * {@inheritdoc}
     */
    public function save($tmpPath, $id, $fileName, $options)
    {
        // TODO: Implement save() method.
    }

    /**
     * {@inheritdoc}
     */
    public function purge($id, $fileName, $options)
    {
        // TODO: Implement purge() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaUrl($media, $format)
    {
        // TODO: Implement getMediaUrl() method.
    }

    /**
     * {@inheritdoc}
     */
    public function analyzedMediaUrl($url)
    {
        if (empty($url)) {
            throw new ImageProxyUrlNotFoundException('The given url was empty');
        }

        $id = $this->getIdFromUrl($url);
        $format = $this->getFormatFromUrl($url);

        return array($id, $format);
    }

    /**
     * return the id of by a given url
     * @param $url
     * @return mixed
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl
     */
    protected function getIdFromUrl($url)
    {
        $fileName = basename($url);
        $idParts = explode('-', $fileName);

        if (count($idParts) < 2) {
            throw new ImageProxyInvalidUrl('No `id` was found in the url');
        }

        $id = $idParts[0];

        if (preg_match('/[^0-9]/', $id)) {
            throw new ImageProxyInvalidUrl('The founded `id` was not a valid integer');
        }

        return $id;
    }

    /**
     * return the format by a given url
     * @param $url
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl
     */
    protected function getFormatFromUrl($url)
    {
        $path = dirname($url);

        $formatParts = array_reverse(explode('/', $path));

        if (count($formatParts) < 2) {
            throw new ImageProxyInvalidUrl('No `format` was found in the url');
        }

        $format = $formatParts[1];

        return $format;
    }
} 
