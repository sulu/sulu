<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
    const TYPE_REMOTE = 'remote';

    const TYPE_LOCAL = 'local';

    /**
     * Save the document in a storage and give back the path to the document.
     */
    public function save(string $tempPath, string $fileName, array $storageOptions = []): array;

    /**
     * Returns the content for the given file as a resource.
     *
     * @return resource
     */
    public function load(array $storageOptions);

    /**
     * Returns the path for the given file.
     */
    public function getPath(array $storageOptions): string;

    /**
     * Returns the type for the given file.
     */
    public function getType(array $storageOptions): string;

    /**
     * Removes the file from storage.
     */
    public function remove(array $storageOptions): void;
}
