<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * Count tags occurrences.
     *
     * @param string $html
     *
     * @return int
     */
    public function count($html);

    /**
     * Returns found tags and their attributes.
     *
     * @param string $html
     *
     * @return TagMatchGroup[]
     */
    public function extract($html);
}
