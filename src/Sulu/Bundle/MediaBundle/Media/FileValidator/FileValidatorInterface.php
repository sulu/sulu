<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FileValidator;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Defines the operations of the FileValidator.
 * The FileValidator is a interface to validate uploaded files.
 */
interface FileValidatorInterface
{
    public const VALIDATOR_FILE_SET = 'FILE_SET';

    public const VALIDATOR_FILE_ERRORS = 'FILE_ERRORS';

    public const VALIDATOR_BLOCK_FILE_TYPES = 'BLOCK_FILE_TYPES';

    public const VALIDATOR_MAX_FILE_SIZE = 'MAX_FILE_SIZE';

    /**
     * Validated a given file.
     *
     * @param array $methods
     */
    public function validate(UploadedFile $file, $methods = []);
}
