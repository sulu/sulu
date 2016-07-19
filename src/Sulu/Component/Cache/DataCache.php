<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * DataCache manages serialized data cached in a file.
 *
 * When the file exists the cache uses the data in the
 * file and does not rely on other files.
 */
class DataCache implements CacheInterface
{
    /**
     * @var string
     */
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (!is_file($this->file)) {
            return;
        }

        return unserialize(file_get_contents($this->file));
    }

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        $mode = 0666;
        $umask = umask();
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->file, serialize($data), null);
        try {
            $filesystem->chmod($this->file, $mode, $umask);
        } catch (IOException $e) {
            // discard chmod failure (some filesystem may not support it)
        }
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh()
    {
        return is_file($this->file);
    }
}
