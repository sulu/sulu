<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

/**
 * Generates the resource-locator with the full-tree.
 */
class TreeGenerator implements ResourceLocatorGeneratorInterface
{
    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * TreeGenerator constructor.
     *
     * @param SlugifierInterface $slugifier
     */
    public function __construct(SlugifierInterface $slugifier)
    {
        $this->slugifier = $slugifier;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($title, $parentPath = null)
    {
        // slugify/urlize title to make resulting path url-friendly
        $origTitle = $title;
        $title = $this->slugifier->slugify($title);
        if ($title === '' && $origTitle !== '') {
            $title = md5($origTitle);
        }

        // if parent has no resource create a new tree
        if ($parentPath == null) {
            return '/' . $title;
        }

        // concat parentPath and title to whole tree path
        return $parentPath . '/' . $title;
    }
}
