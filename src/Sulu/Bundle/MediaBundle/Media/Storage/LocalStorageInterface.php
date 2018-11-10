<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

interface LocalStorageInterface extends StorageInterface
{
    /**
     * Returns the path for the given file.
     */
    public function getLocalPath(array $storageOption): ?string;
}
