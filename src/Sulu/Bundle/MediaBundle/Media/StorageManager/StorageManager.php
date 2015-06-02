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
    protected $defaultName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string $defaultName
     * @param LoggerInterface $logger
     */
    public function __construct(
        $defaultName,
        $logger = null
    ) {
        $this->defaultName = $defaultName;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $storageOption = null, $storageName = null)
    {
        return $this->get($storageName)->save($tempPath, $fileName, $storageOption);
    }

    /**
     * {@inheritdoc}
     */
    public function load($storageOption, $storageName = null)
    {
        return $this->get($storageName)->load($storageOption);
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
    public function getDefaultName()
    {
        return $this->defaultName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDownloadUrl($storageOption, $storageName = null)
    {
        return $this->get($storageName)->getDownloadUrl($storageOption);
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
            $name = $this->getDefaultName();
        }

        if (!isset($this->storages[$name])) {
            throw new StorageNotFoundException($name);
        }

        return $this->storages[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getNames()
    {
        return array_keys($this->storages);
    }
}
