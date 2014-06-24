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
     * @param string $fileName
     * @param int $version
     * @param array $storageOptions
     * @return string
     */
    public function getOriginal($fileName, $version, $storageOptions);

    /**
     * @param int $id
     * @param string $fileName
     * @param array $storageOptions
     * @return array
     */
    public function getFormats($id, $fileName, $storageOptions);
} 
