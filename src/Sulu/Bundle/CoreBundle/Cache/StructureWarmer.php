<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Cache;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Generates the Structure cache files.
 */
class StructureWarmer implements CacheWarmerInterface
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    public function __construct(StructureManagerInterface $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * Warms up the cache.
     *
     * @return string[] A list of classes or files to preload on PHP 7.4+
     */
    public function warmUp($cacheDir)
    {
        // warmup the pages
        $this->structureManager->getStructures(Structure::TYPE_PAGE);

        // warm up the snippets
        $this->structureManager->getStructures(Structure::TYPE_SNIPPET);

        return [];
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return true;
    }
}
