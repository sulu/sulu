<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Slugifier;

use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

/**
 * Wraps default slugifier and add some additional node-name stuff.
 */
class NodeNameSlugifier implements SlugifierInterface
{
    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @param SlugifierInterface $slugifier
     */
    public function __construct(SlugifierInterface $slugifier)
    {
        $this->slugifier = $slugifier;
    }

    /**
     * Slugifies given string to a valid node-name.
     *
     * @param string $text
     *
     * @return string
     */
    public function slugify($text)
    {
        $text = $this->slugifier->slugify($text);

        // jackrabbit can not handle node-names which contains a number followed by "e" e.g. 10e
        $text = preg_replace('((\d+)([eE]))', '$1-$2', $text);

        return $text;
    }
}
