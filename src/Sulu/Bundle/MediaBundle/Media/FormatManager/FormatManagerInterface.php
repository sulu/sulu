<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the operations of the FormatManager
 * The FormatManager is a interface to manage Image Formats and Converts.
 *
 * @phpstan-type TypeDefinition array{
 *   internal:mixed,
 *   key: string,
 *   title: string,
 *   scale: mixed
 * }
 */
interface FormatManagerInterface
{
    /**
     * Return the image by a given url.
     *
     * @param int $id
     * @param string $formatKey
     * @param string $imageFormat
     *
     * @return Response
     */
    public function returnImage($id, $formatKey, $imageFormat);

    /**
     * @param int $id
     * @param string $fileName
     * @param int $version
     * @param int $subVersion
     * @param string|null $mimeType
     *
     * @return array
     */
    public function getFormats($id, $fileName, $version, $subVersion, $mimeType);

    /**
     * Returns a definition of a format with a given key.
     *
     * @param string $formatKey
     * @param string|null $locale
     *
     * @return TypeDefinition
     */
    public function getFormatDefinition($formatKey, $locale = null);

    /**
     * Returns all definitions of image formats.
     *
     * @param string $locale
     *
     * @return array<array-key, TypeDefinition>
     */
    public function getFormatDefinitions($locale = null);

    /**
     * Delete the image by the given parameters.
     *
     * @param int $idMedia
     * @param string $fileName
     * @param string|null $mimeType
     *
     * @return bool
     */
    public function purge($idMedia, $fileName, $mimeType);

    /**
     * Clears the format cache.
     *
     * @return void
     */
    public function clearCache();
}
