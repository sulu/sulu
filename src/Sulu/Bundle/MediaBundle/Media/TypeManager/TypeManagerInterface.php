<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\TypeManager;

/**
 * Interface TypeManagerInterface
 * The Type Manager returns the media types or return a media type for a specific mime type.
 */
interface TypeManagerInterface
{
    /**
     * Returns a Media Type ID by a given mime type.
     */
    public function getMediaType(?string $fileMimeType): ?string;
}
