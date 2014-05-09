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
 * @package Sulu\Bundle\TagBundle\Tag\Exception
 */
class UploadFileValidationException extends Exception
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
    const EXCEPTION_COLLECTION_NOT_FOUND = 5005;

    /**
     * @param string $message
     * @param int $code
     */
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
