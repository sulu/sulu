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
 * Uses multiple tag-extractor to extract all special tags from given html.
 */
class DelegatingTagExtractor implements TagExtractorInterface
{
    /**
     * @var TagExtractorInterface[]
     */
    private $pool;

    /**
     * @param TagExtractorInterface[] $pool
     */
    public function __construct(array $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function count($html)
    {
        $result = 0;
        foreach ($this->pool as $tagExtractor) {
            $result += $tagExtractor->count($html);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($html)
    {
        $result = [];
        foreach ($this->pool as $tagExtractor) {
            $result = array_merge($result, $tagExtractor->extract($html));
        }

        return $result;
    }
}
