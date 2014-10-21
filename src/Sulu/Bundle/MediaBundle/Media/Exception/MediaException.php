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
 * @package Sulu\Bundle\MediaBundle\Media\Exception
 */
class MediaException extends Exception
{
    /**
     * @var int
     * @description this exception code is thrown when $_FILES['error'] > 0
     */
    const EXCEPTION_CODE_UPLOAD_ERROR = 5001;

    /**
     * @var int
     * @description this exception code is thrown when the uploaded file was not found
     */
    const EXCEPTION_CODE_UPLOADED_FILE_NOT_FOUND = 5002;

    /**
     * @var int
     * @description this exception code is thrown when the file is bigger as the max file size in the config
     */
    const EXCEPTION_CODE_MAX_FILE_SIZE = 5003;

    /**
     * @var int
     * @description this exception code is thrown when the file type is not supported
     */
    const EXCEPTION_CODE_BLOCKED_FILE_TYPE = 5004;

    /**
     * @var int
     * @description this exception code is thrown when the file type is not supported
     */
    const EXCEPTION_CODE_COLLECTION_NOT_FOUND = 5005;

    /**
     * @var int
     * @description this exception code is thrown when the file version to update was not found
     */
    const EXCEPTION_CODE_FILE_VERSION_NOT_FOUND = 5006;

    /**
     * @var int
     * @description this exception code is thrown when the file has not the correct media type as the followed file versions
     */
    const EXCEPTION_CODE_INVALID_MEDIA_TYPE = 5007;

    /**
     * @var int
     * @description image id was not found in request
     */
    const EXCEPTION_CODE_IMAGE_PROXY_MEDIA_ID_NOT_FOUND = 5008;

    /**
     * @var int
     * @description media not loaded by proxy id
     */
    const EXCEPTION_CODE_IMAGE_PROXY_MEDIA_NOT_FOUND = 5009;

    /**
     * @var int
     * @description original image not found
     */
    const EXCEPTION_CODE_IMAGE_PROXY_ORIGINAL_NOT_FOUND = 5010;

    /**
     * @var int
     * @description the image url was not found
     */
    const EXCEPTION_CODE_IMAGE_PROXY_URL_NOT_FOUND = 5011;

    /**
     * @var int
     * @description the image url was not valid
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_URL = 5012;

    /**
     * @var int
     * @description the image format was not found
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_IMAGE_FORMAT = 5013;

    /**
     * @var int
     * @description the configured format options are invalid
     */
    const EXCEPTION_CODE_IMAGE_PROXY_INVALID_FORMAT_OPTIONS = 5014;

    /**
     * @var int
     * @description the media was not found
     */
    const EXCEPTION_CODE_MEDIA_NOT_FOUND = 5015;

    /**
     * @var int
     * @description the collection type was not found
     */
    const EXCEPTION_CODE_COLLECTION_TYPE_NOT_FOUND = 5016;

    /**
     * @var int
     * @description the media type was not found
     */
    const EXCEPTION_CODE_MEDIA_TYPE_NOT_FOUND = 5017;

    /**
     * @var int
     * @description no previews are generated for this extension
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

    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message
        );
    }
}
