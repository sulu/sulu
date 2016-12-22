<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the operations of the FormatManager
 * The FormatManager is a interface to manage Image Formats and Converts.
 */
interface FormatManagerInterface
{
    /**
     * Return the image by a given url.
     *
     * @param int $id
     * @param string $formatName
     *
     * @return Response
     */
    public function returnImage($id, $formatName);

    /**
     * Return media id and format.
     *
     * @param string $url
     *
     * @return array
     */
    public function getMediaProperties($url);

    /**
     * @param int $id
     * @param string $fileName
     * @param array $storageOptions
     * @param int $version
     * @param int $subVersion
     * @param string $mimeType
     *
     * @return array
     */
    public function getFormats($id, $fileName, $storageOptions, $version, $subVersion, $mimeType);

    /**
     * Returns a definition of a format with a given key. The returned formats contain
     * the passed format options merged into them.
     *
     * @param string $formatKey
     * @param string $locale
     * @param array $formatOptions
     *
     * @return array
     */
    public function getFormatDefinition($formatKey, $locale = null, array $formatOptions = []);

    /**
     * Returns all definitions of image formats. The returned formats contain the passed
     * format-options merged into them.
     *
     * @param string $locale
     * @param array $formatOptions
     *
     * @return array
     */
    public function getFormatDefinitions($locale = null, array $formatOptions = []);

    /**
     * Delete the image by the given parameters.
     *
     * @param int $idMedia
     * @param string $fileName
     * @param string $mimeType
     * @param string $options
     *
     * @return bool
     */
    public function purge($idMedia, $fileName, $mimeType, $options);

    /**
     * Clears the format cache.
     */
    public function clearCache();
}
