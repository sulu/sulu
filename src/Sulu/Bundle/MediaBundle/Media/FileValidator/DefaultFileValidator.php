<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FileValidator;

use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileTypeException;
use Sulu\Bundle\MediaBundle\Media\Exception\MaxFileSizeExceededException;
use Sulu\Bundle\MediaBundle\Media\Exception\UploadFileNotSetException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DefaultFileValidator implements FileValidatorInterface
{
    /**
     * @var array
     */
    protected $blockedMimeTypes = array();

    /**
     * @var int
     */
    protected $maxFileSize = 0;

    /**
     * @param UploadedFile $file
     * @param array $methods
     * @return mixed|void
     * @throws InvalidFileTypeException
     * @throws InvalidFileException
     * @throws UploadFileNotSetException
     * @throws MaxFileSizeExceededException
     */
    public function validate (UploadedFile $file, $methods = array(
            self::VALIDATOR_FILE_SET,
            self::VALIDATOR_FILE_ERRORS,
            self::VALIDATOR_BLOCK_FILE_TYPES,
            self::VALIDATOR_MAX_FILE_SIZE
        ))
    {
        if (in_array(self::VALIDATOR_FILE_ERRORS, $methods) && $file->getError() > 0) {
            throw new InvalidFileException('The file upload had an error('.$file->getError().'): ' . $file->getErrorMessage());
        }

        if (in_array(self::VALIDATOR_FILE_SET, $methods) && $file->getFilename() == '') {
            throw new UploadFileNotSetException('No file was set');
        }

        if (in_array(self::VALIDATOR_BLOCK_FILE_TYPES, $methods) && in_array($file->getMimeType(), $this->blockedMimeTypes)) {
            throw new InvalidFileTypeException('The file type was blocked');
        }

        if (in_array(self::VALIDATOR_MAX_FILE_SIZE, $methods) && $file->getSize() <= $this->maxFileSize) {
            throw new MaxFileSizeExceededException('The file is to big');
        }
    }

    /**
     * @param string $maxFileSize
     */
    public function setMaxFileSize($maxFileSize)
    {
        $digitalUnits = array(
            'B'  => 1,
            'KB' => 1024,
            'MB' => 1048576,
            'GB' => 1073741824,
            'TB' => 1099511627776,
        );
        $defaultUnit = 'B';

        $value = intval($maxFileSize);
        $maxFileSizeParts = preg_split('/\d+/', $maxFileSize);
        $digitalUnit = isset($maxFileSizeParts[1]) ? $maxFileSizeParts[1] : $defaultUnit;

        $unitInBytes = isset($digitalUnits[strtoupper($digitalUnit)]) ? $digitalUnits[strtoupper($digitalUnit)] : $digitalUnits[$defaultUnit];

        $this->maxFileSize = $value * $unitInBytes;
    }

    /**
     * @return array
     */
    public function getBlockedMimeTypes()
    {
        return $this->blockedMimeTypes;
    }

    /**
     * @param array $blockedMimeTypes
     */
    public function setBlockedMimeTypes($blockedMimeTypes)
    {
        $this->blockedMimeTypes = $blockedMimeTypes;
    }

    /**
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }
}
