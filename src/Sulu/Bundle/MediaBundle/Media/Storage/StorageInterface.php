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
    public const TYPE_REMOTE = 'remote';

    public const TYPE_LOCAL = 'local';

    /**
     * Save the document in the storage and return storage options of the stored document.
     *
     * @param array<string, string|null> $storageOptions
     */
    public function save(string $tempPath, string $fileName, array $storageOptions = []): array;

    /**
     * Returns the content for the given file as a resource.
     *
     * @param array<string, string|null> $storageOptions
     *
     * @return resource
     */
    public function load(array $storageOptions);

    /**
     * Returns the path for the given file.
     *
     * @param array<string, string|null> $storageOptions
     */
    public function getPath(array $storageOptions): string;

    /**
     * Returns the type for the given file.
     *
     * @param array<string, string|null> $storageOptions
     */
    public function getType(array $storageOptions): string;

    /**
     * Moves a file on the storage.
     *
     * @param array<string, string|null> $sourceStorageOptions
     * @param array<string, string|null> $targetStorageOptions
     */
    public function move(array $sourceStorageOptions, array $targetStorageOptions): void;

    /**
     * Removes the file from storage.
     *
     * @param array<string, string|null> $storageOptions
     */
    public function remove(array $storageOptions): void;
}
