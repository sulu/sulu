<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\DispositionType;

/**
 * Interface for implementing disposition type resolver.
 */
interface DispositionTypeResolverInterface
{
    /**
     * Get disposition type for passed mime type.
     *
     * @param string $mimeType
     *
     * @return string
     */
    public function getByMimeType($mimeType);
}
