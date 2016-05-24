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
     *
     * @return string
     */
    public function parse($content);
}
