<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

/**
 * Provides Twig functions to handle snippets.
 */
interface SnippetTwigExtensionInterface extends \Twig_ExtensionInterface
{
    /**
     * Returns snippet.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return array
     */
    public function loadSnippet($uuid, $locale = null);
}
