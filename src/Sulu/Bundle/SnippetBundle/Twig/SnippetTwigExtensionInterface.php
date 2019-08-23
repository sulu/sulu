<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

use Twig\Extension\ExtensionInterface;

/**
 * Provides Twig functions to handle snippets.
 */
interface SnippetTwigExtensionInterface extends ExtensionInterface
{
    /**
     * Returns snippet.
     *
     * @param string $uuid
     * @param string|null $locale
     * @param bool $loadExcerpt
     *
     * @return array
     */
    public function loadSnippet($uuid, $locale = null, $loadExcerpt = false);
}
