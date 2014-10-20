<?php
/*
 * This file is part of the Sulu CMS.
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
 * @package Sulu\Bundle\MediaBundle\Media\FormatManager
 */
interface FormatManagerInterface
{
    /**
     * Return the image by a given url
     * @param int $id
     * @param string $format
     * @return Response
     */
    public function returnImage($id, $format);

    /**
     * Return media id and format
     * @param string $url
     * @return array
     */
    public function getMediaProperties($url);

    /**
     * @param int $id
     * @param string $fileName
     * @param array $storageOptions
     * @param int $version
     * @return array
     */
    public function getFormats($id, $fileName, $storageOptions, $version);

    /**
     * Delete the image by the given parameters
     * @param int $idMedia
     * @param string $fileName
     * @param array $options
     * @return bool
     */
    public function purge($idMedia, $fileName, $options);
} 
