<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatCache;

use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyUrlNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class LocalFormatCache implements FormatCacheInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $pathUrl;

    /**
     * @var int
     */
    protected $segments;

    /**
     * @var array
     */
    protected $formats;

    public function __construct(Filesystem $filesystem, $path, $pathUrl, $segments, $formats)
    {
        $this->filesystem = $filesystem;
        $this->path = $path;
        $this->pathUrl = $pathUrl;
        $this->segments = intval($segments);
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function save($content, $id, $fileName, $options, $format)
    {
        $savePath = $this->getPath($this->path, $id, $fileName, $format);
        if (!is_dir(dirname($savePath))) {
            $this->filesystem->mkdir(dirname($savePath), 0775);
        }

        try {
            $this->filesystem->dumpFile($savePath, $content);
        } catch (IOException $ioException) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($id, $fileName, $options)
    {
        foreach ($this->formats as $format) {
            $path = $this->getPath($this->path, $id, $fileName, $format['key']);
            $this->filesystem->remove($path);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaUrl($id, $fileName, $options, $format, $version, $subVersion)
    {
        return $this->getPathUrl($this->pathUrl, $id, $fileName, $format, $version, $subVersion);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $realCacheDir = $this->path;
        $oldCacheDir = $realCacheDir . '_old';

        if (!is_writable($realCacheDir)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $realCacheDir));
        }

        if ($this->filesystem->exists($oldCacheDir)) {
            $this->filesystem->remove($oldCacheDir);
        }

        $this->filesystem->rename($realCacheDir, $oldCacheDir);
        $this->filesystem->mkdir($realCacheDir);
        $this->filesystem->remove($oldCacheDir);
    }

    /**
     * @param string $prePath
     * @param int $id
     * @param string $fileName
     * @param string $format
     *
     * @return string
     */
    protected function getPath($prePath, $id, $fileName, $format)
    {
        $segment = $this->getSegment($id) . '/';
        $prePath = rtrim($prePath, '/');

        return $prePath . '/' . $format . '/' . $segment . $id . '-' . $fileName;
    }

    /**
     * @param string $prePath
     * @param int $id
     * @param string $fileName
     * @param string $format
     * @param string $version
     * @param string $subVersion
     *
     * @return string
     */
    protected function getPathUrl($prePath, $id, $fileName, $format, $version = '', $subVersion = '')
    {
        $segment = $this->getSegment($id) . '/';
        $prePath = rtrim($prePath, '/');

        return str_replace(
            '{slug}',
            $format . '/' . $segment . $id . '-' . rawurlencode($fileName),
            $prePath
        ) . '?v=' . $version . '-' . $subVersion;
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

        return [$id, $format];
    }

    /**
     * return the id of by a given url.
     *
     * @param string $url
     *
     * @return int
     *
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
     * @param $id
     *
     * @return string
     */
    protected function getSegment($id)
    {
        return sprintf('%0' . strlen($this->segments) . 'd', ($id % $this->segments));
    }

    /**
     * return the format by a given url.
     *
     * @param string $url
     *
     * @return string
     *
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
