<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Exception;

use Exception;

/**
 * This Exception is thrown when a Uploaded File is not valid
 */
class MediaException extends Exception
{
    /**
     * this exception code is thrown when $_FILES['error'] > 0
     *
     * @var int
     */
    const EXCEPTION_CODE_UPLOAD_ERROR = 5001;

    /**
     * this exception code is thrown when the uploaded file was not found
     *
     * @var int
     */
    const EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND = 5002;

    /**
     * this exception code is thrown when the file is bigger as the max file size in the config
     *
     * @var int
     */
    const EXCEPTION_CODE_MAX_FILE_SIZE = 5003;

    /**
     * this exception code is thrown when the file type is not supported
     *
     * @var int
     */
    const EXCEPTION_CODE_BLOCKED_FILE_TYPE = 5004;

    /**
     * this exception code is thrown when the file type is not supported
     *
     * @var int
     */
    const EXCEPTION_CODE_COLLECTION_NOT_FOUND = 5005;

    /**
     * this exception code is thrown when the file version to update was not found
     *
     * @var int
     */
    const EXCEPTION_CODE_FILE_VERSION_NOT_FOUND = 5006;

    /**
     * this exception code is thrown when the file has not the correct media type as the followed file versions
     *
     * @var int
     */
    const EXCEPTION_CODE_INVALID_MEDIA_TYPE = 5007;

    /**
     * image id was not found in request
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_MEDIA_ID_NOT_FOUND = 5008;

    /**
     * media not loaded by proxy id
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_MEDIA_NOT_FOUND = 5009;

    /**
     * original image not found
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_ORIGINAL_NOT_FOUND = 5010;

    /**
     * the image url was not found
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_URL_NOT_FOUND = 5011;

    /**
     * @var int
     * @description the image url was not valid
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_URL = 5012;

    /**
     * the image format was not found
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_IMAGE_FORMAT = 5013;

    /**
     * the configured format options are invalid
     *
     * @var int
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_FORMAT_OPTIONS = 5014;

    /**
     * the media was not found
     *
     * @var int
     */
    const EXCEPTION_CODE_MEDIA_NOT_FOUND = 5015;

    /**
     * the collection type was not found
     *
     * @var int
     */
    const EXCEPTION_CODE_COLLECTION_TYPE_NOT_FOUND = 5016;

    /**
     * the media type was not found
     *
     * @var int
     */
    const EXCEPTION_CODE_MEDIA_TYPE_NOT_FOUND = 5017;

    /**
     * no previews are generated for this extension
     *
     * @var int
     */
    const EXCEPTION_INVALID_MIMETYPE_FOR_PREVIEW = 5018;

    /**
     * @var int
     * @description ghostscript was not found at location
     */
    const EXCEPTION_CODE_GHOST_SCRIPT_NOT_FOUND = 5019;

    /**
     * @var int
     * @description a file with this name exists
     */
    const EXCEPTION_FILENAME_ALREADY_EXISTS = 5020;

    /**
     * this exception code is thrown when the file is not found
     *
     * @var int
     */
    const EXCEPTION_CODE_FILE_NOT_FOUND = 5021;

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message
        );
    }
}
