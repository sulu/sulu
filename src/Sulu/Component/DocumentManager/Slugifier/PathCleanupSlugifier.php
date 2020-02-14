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
    /** @var PathCleanupInterface */
    private $pathCleanup;

    public function __construct(PathCleanupInterface $pathCleanup)
    {
        $this->pathCleanup = $pathCleanup;
    }

    public function slugify($text)
    {
        $text = str_replace('/', '-', $text);

        // Remove apostrophes which are not used as quotes around a string
        $text = preg_replace('/(\\w)\'(\\w)/', '${1}${2}', $text);

        // Replace all none word characters with a space
        $text = preg_replace('/\W/', ' ', $text);

        $text = preg_replace('/([a-z\d])([A-Z])/', '\1_\2', $text);
        $text = preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2', $text);
        $text = preg_replace('/::/', '.', $text);

        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text);
        } else {
            $text = strtolower($text);
        }

        return trim($this->pathCleanup->cleanup($text), '-');
    }
}
