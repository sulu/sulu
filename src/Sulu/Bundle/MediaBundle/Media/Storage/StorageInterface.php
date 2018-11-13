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
    const PATH_TYPE_REDIRECT = 'redirect';

    const PATH_TYPE_FILE = 'file';

    /**
     * Save the document in a storage and give back the path to the document.
     */
    public function save(string $tempPath, string $fileName, array $storageOption = []): array;

    /**
     * Returns the content for the given file as a resource.
     *
     * @return resource
     */
    public function load(array $storageOption);

    /**
     * Returns the path for the given file.
     */
    public function getPath(array $storageOption): string;

    /**
     * Returns the path-type for the given file.
     */
    public function getPathType(array $storageOption): string;

    /**
     * Removes the file from storage.
     */
    public function remove(array $storageOption): void;
}
