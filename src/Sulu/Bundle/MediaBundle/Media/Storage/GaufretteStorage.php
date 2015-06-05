<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

use Gaufrette\Filesystem;
use Gaufrette\FilesystemMap;

/**
 * Class GaufretteStorage
 * @package Sulu\Bundle\MediaBundle\Media\Storage
 */
class GaufretteStorage implements StorageInterface
{
    /**
     * @var $type
     */
    protected $filesystem;

    /**
     * @var FilesystemMap
     */
    protected $filesystemMap;

    /**
     * @param string $filesystem
     * @param FilesystemMap $filesystemMap
     */
    public function __construct(
        $filesystem,
        FilesystemMap $filesystemMap
    ) {
        $this->filesystem = $filesystem;
        $this->filesystemMap = $filesystemMap;
    }

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $storageOption = null)
    {
        // TODO: Implement save() method.
    }

    /**
     * {@inheritdoc}
     */
    public function load($storageOption)
    {
        // TODO: Implement load() method.
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOption)
    {
        // TODO: Implement remove() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getDownloadUrl($storageOption)
    {
        return null;
    }

    /**
     * @return Filesystem
     */
    protected function getFileSystem()
    {
        $this->filesystemMap->get($this->filesystem);
    }
}
