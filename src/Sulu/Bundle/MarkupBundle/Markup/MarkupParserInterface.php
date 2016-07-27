<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup;

/**
 * Parses response and replaces special tags.
 */
interface MarkupParserInterface
{
    /**
     * Parses document and returns completed document.
     *
     * @param string $content
     * @param string $locale
     *
     * @return string
     */
    public function parse($content, $locale);

    /**
     * Validates document and returns validity and content with marker of invalid tags.
     *
     * If resulting array is empty the content is valid.
     *
     * @param string $content
     * @param string $locale
     *
     * @return array
     */
    public function validate($content, $locale);
}
