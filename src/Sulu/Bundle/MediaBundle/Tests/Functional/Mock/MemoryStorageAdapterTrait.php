<?php

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Mock;

use League\Flysystem\Config;

trait MemoryStorageAdapterTrait
{
    /**
     * @var array
     */
    private $objectMap = [];

    public function addFile(string $filePath, string $content): void
    {
        $this->objectMap[$filePath] = $content;
    }

    public function addDirectory(string $directoryPath): void
    {
        $this->objectMap[$directoryPath] = null;
    }

    public function has($path)
    {
        return array_key_exists($path, $this->objectMap);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $this->objectMap[$path] = $contents;

        $type = 'file';
        $result = compact('contents', 'type', 'path');

        if ($visibility = $config->get('visibility')) {
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        if (array_key_exists($path, $this->objectMap)) {
            return ['contents' => $this->objectMap[$path]];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        unset($this->objectMap[$path]);

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        return compact('visibility');
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $this->objectMap[$dirname] = null;

        return ['path' => $dirname, 'type' => 'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        return false;
    }
}
