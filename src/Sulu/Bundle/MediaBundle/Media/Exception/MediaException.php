<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

use Exception;

/**
 * This Exception is thrown when a Uploaded File is not valid.
 */
class MediaException extends Exception
{
    /**
     * Used when $_FILES['error'] > 0.
     *
     * @var int
     */
    const EXCEPTION_CODE_UPLOAD_ERROR = 5001;

    /**
     * The uploaded file was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND = 5002;

    /**
     * The file is bigger as the max file size in the config.
     *
     * @var int
     */
    const EXCEPTION_CODE_MAX_FILE_SIZE = 5003;

    /**
     * The file mime type is not supported.
     *
     * @var int
     */
    const EXCEPTION_CODE_BLOCKED_FILE_TYPE = 5004;

    /**
     * The collection was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_COLLECTION_NOT_FOUND = 5005;

    /**
     * The file version was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_FILE_VERSION_NOT_FOUND = 5006;

    /**
     * The file has not the correct media type as the followed file versions.
     *
     * @var int
     */
    const EXCEPTION_CODE_INVALID_MEDIA_TYPE = 5007;

    /**
     * Image id was not found in request.
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_MEDIA_ID_NOT_FOUND = 5008;

    /**
     * Media not loaded by proxy id.
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_MEDIA_NOT_FOUND = 5009;

    /**
     * Original image not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_ORIGINAL_NOT_FOUND = 5010;

    /**
     * The image url was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_URL_NOT_FOUND = 5011;

    /**
     * The image url was not valid.
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_URL = 5012;

    /**
     * the image format was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_IMAGE_FORMAT = 5013;

    /**
     * The configured format options are invalid.
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_FORMAT_OPTIONS = 5014;

    /**
     * The media was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_MEDIA_NOT_FOUND = 5015;

    /**
     * The collection type was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_COLLECTION_TYPE_NOT_FOUND = 5016;

    /**
     * The media type was not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_MEDIA_TYPE_NOT_FOUND = 5017;

    /**
     * No previews are generated for this extension.
     *
     * @var int
     */
    const EXCEPTION_INVALID_MIMETYPE_FOR_PREVIEW = 5018;

    /**
     * Ghostscript was not found at location.
     *
     * @var int
     */
    const EXCEPTION_CODE_GHOST_SCRIPT_NOT_FOUND = 5019;

    /**
     * A file with this name exists.
     *
     * @var int
     */
    const EXCEPTION_FILENAME_ALREADY_EXISTS = 5020;

    /**
     * File is not found in media object.
     *
     * @var int
     */
    const EXCEPTION_CODE_FILE_NOT_FOUND = 5021;

    /**
     * Format cache is not found.
     *
     * @var int
     */
    const EXCEPTION_CACHE_NOT_FOUND = 5022;

    /**
     * Systemfile is not found.
     *
     * @var int
     */
    const EXCEPTION_CODE_ORIGINAL_FILE_NOT_FOUND = 5027;

    /**
     * Format is not found.
     *
     * @var int
     */
    const EXCEPTION_FORMAT_NOT_FOUND = 5028;

    /**
     * Format options parameter is missing.
     *
     * @var int
     */
    const EXCEPTION_FORMAT_OPTIONS_MISSING_PARAMETER = 5029;

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
