<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\DispositionType;

use Sulu\Bundle\MediaBundle\Entity\FileVersion;

/**
 * Interface for implementing disposition type service.
 */
interface DispositionTypeServiceInterface
{
    /**
     * Get disposition type for passed mime type
     *
     * @param string $mimeType
     * @return string
     */
    public function getMimeTypeDispositionType($mimeType);

    /**
     * Get disposition type for passed FileVersion object
     *
     * @param FileVersion $fileVersion
     * @return string
     */
    public function getFileVersionDispositionType(FileVersion $fileVersion);
}