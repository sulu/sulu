<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Defines the operations of the StorageLayer.
 * The StorageLayer is a interface to centralized management of media store
 * @package Sulu\Bundle\MediaBundle\Media
 */
interface FileValidatorInterface
{
    const VALIDATOR_FILE_SET = 'FILE_SET';
    const VALIDATOR_FILE_ERRORS = 'FILE_ERRORS';
    const VALIDATOR_BLOCK_FILE_TYPES = 'BLOCK_FILE_TYPES';
    const VALIDATOR_MAX_FILE_SIZE = 'MAX_FILE_SIZE';

    public function validate(UploadedFile $file, $methods = array());
}
