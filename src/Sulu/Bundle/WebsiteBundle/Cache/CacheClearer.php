<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Clear http_cache for website.
 */
class CacheClearer implements CacheClearerInterface
{
    /**
     * @var string
     */
    private $kernelRootDir;

    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $varDir;

    /**
     * @param Filesystem $filesystem
     * @param $kernelEnvironment
     * @param $kernelRootDir
     * @param string $varDir
     */
    public function __construct(Filesystem $filesystem, $kernelEnvironment, $kernelRootDir, $varDir = null)
    {
        $this->kernelRootDir = $kernelRootDir;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->filesystem = $filesystem;
        $this->varDir = $varDir;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $path = sprintf(
            '%s/cache/website/%s/http_cache',
            $this->varDir ?: $this->kernelRootDir,
            $this->kernelEnvironment
        );

        if ($this->filesystem->exists($path)) {
            $this->filesystem->remove($path);
        }
    }
}
