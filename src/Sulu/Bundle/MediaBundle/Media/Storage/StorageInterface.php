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

/**
 * Defines the operations of the StorageLayer.
 * The StorageLayer is a interface to centralized management of media store.
 */
interface StorageInterface
{
    /**
     * Save the document in a storage and give back the path to the document.
     */
    public function save(string $tempPath, string $fileName, int $version): string;

    /**
     * Returns the content for the given file as a resource.
     *
     * @return resource
     */
    public function loadAsStream(string $fileName, int $version, string $storageOption);

    /**
     * Returns the content for the given file as a binary string.
     */
    public function loadAsString(string $fileName, int $version, string $storageOption): string;

    /**
     * Removes the file from storage.
     */
    public function remove(string $storageOption): void;
}
