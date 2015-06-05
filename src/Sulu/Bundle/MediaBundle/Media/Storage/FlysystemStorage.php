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

use League\Flysystem\MountManager;
use League\Flysystem\AdapterInterface;

/**
 * Class GaufretteStorage
 * @package Sulu\Bundle\MediaBundle\Media\Storage
 */
class FlysystemStorage implements StorageInterface
{
    /**
     * @var $type
     */
    protected $type;

    /**
     * @param string $type
     */
    public function __construct(
        $type,
        MountManager $mountManager
    ) {
        $this->type = $type;
        $this->mountManager = $mountManager;
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
     * @return AdapterInterface
     */
    protected function getFilesystem()
    {
        return $this->mountManager->getFilesystem($this->type);
    }
}
