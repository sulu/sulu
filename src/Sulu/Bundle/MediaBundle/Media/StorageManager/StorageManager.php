<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\StorageManager;

use Sulu\Bundle\MediaBundle\Media\Exception\StorageNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class StorageManager
 * Manage the Media Storages
 */
class StorageManager implements StorageManagerInterface
{
    /**
     * @var StorageInterface[]
     */
    protected $storages = array();

    /**
     * @var string
     */
    protected $defaultStorageName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string $defaultStorageName
     */
    public function __construct(
        $defaultStorageName,
        $logger = null
    ) {
        $this->$defaultStorageName = $defaultStorageName;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $version, $storageOption = null, $storageName = null)
    {
        return $this->get($storageName)->save($tempPath, $fileName, $version, $storageOption);
    }

    /**
     * {@inheritdoc}
     */
    public function load($fileName, $version, $storageOption, $storageName = null)
    {
        return $this->get($storageName)->load($fileName, $version, $storageOption);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOption, $storageName = null)
    {
        return $this->get($storageName)->remove($storageOption);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultStorageName()
    {
        return $this->defaultStorageName;
    }

    /**
     * {@inheritdoc}
     */
    public function add(StorageInterface $command, $name)
    {
        $this->storages[$name] = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name = null)
    {
        if (!$name) {
            $name = $this->getDefaultStorageName();
        }

        if (!isset($this->storages[$name])) {
            throw new StorageNotFoundException($name);
        }

        return $this->storages[$name];
    }
}
