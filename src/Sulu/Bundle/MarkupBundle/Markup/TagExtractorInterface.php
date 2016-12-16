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
 * Interface for tag-extractors.
 */
interface TagExtractorInterface
{
    /**
     * @param string $html
     * @param string $namespace
     *
     * @return int
     */
    public function count($html, $namespace);

    /**
     * Returns found tags and their attributes.
     *
     * @param string $html
     * @param string $namespace
     *
     * @return array
     */
    public function extract($html, $namespace);
}
