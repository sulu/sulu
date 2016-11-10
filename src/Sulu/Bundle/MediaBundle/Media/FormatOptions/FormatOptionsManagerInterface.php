<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatOptions;

use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatOptionsMissingParameterException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;

/**
 * Interface for handling the format options of a media.
 */
interface FormatOptionsManagerInterface
{
    /**
     * Returns the options for a single media identified by the id of the media and the key of the format.
     *
     * @param $mediaId
     * @param $formatKey
     *
     * @throws MediaNotFoundException
     * @throws FormatNotFoundException
     * @throws FileVersionNotFoundException
     *
     * @return array
     */
    public function get($mediaId, $formatKey);

    /**
     * Returns the options for all formats for a single media identified by its id.
     *
     * @param $mediaId
     *
     * @throws MediaNotFoundException
     * @throws FileVersionNotFoundException
     *
     * @return mixed
     */
    public function getAll($mediaId);

    /**
     * Creates or changes a format options with given data.
     *
     * @param int $mediaId
     * @param string $formatKey
     * @param array $data
     *
     * @throws MediaNotFoundException
     * @throws FormatNotFoundException
     * @throws FileVersionNotFoundException
     * @throws FormatOptionsMissingParameterException
     *
     * @return FormatOptions
     */
    public function save($mediaId, $formatKey, array $data);

    /**
     * Deletes a format option, identified by the id of the file-version and the key of the format.
     *
     * @param $mediaId
     * @param $formatKey
     *
     * @throws MediaNotFoundException
     * @throws FormatNotFoundException
     * @throws FileVersionNotFoundException
     */
    public function delete($mediaId, $formatKey);
}
