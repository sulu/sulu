<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Slugifier;

use Sulu\Component\PHPCR\PathCleanupInterface;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

class PathCleanupSlugifier implements SlugifierInterface
{
    public function __construct(private PathCleanupInterface $pathCleanup)
    {
    }

    public function slugify($text)
    {
        $text = \str_replace('/', '-', $text);

        return $this->pathCleanup->cleanup($text);
    }
}
