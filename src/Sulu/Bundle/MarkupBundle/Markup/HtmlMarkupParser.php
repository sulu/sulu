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

use Sulu\Bundle\MarkupBundle\Tag\TagRegistryInterface;

/**
 * Parses html content and replaces special tags.
 */
class HtmlMarkupParser implements MarkupParserInterface
{
    /**
     * @var TagRegistryInterface
     */
    private $tagRegistry;

    /**
     * @var TagExtractorInterface
     */
    private $tagExtractor;

    /**
     * @param TagRegistryInterface $tagRegistry
     * @param TagExtractorInterface $tagExtractor
     */
    public function __construct(TagRegistryInterface $tagRegistry, TagExtractorInterface $tagExtractor)
    {
        $this->tagRegistry = $tagRegistry;
        $this->tagExtractor = $tagExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($content, $locale)
    {
        if (0 === $this->tagExtractor->count($content)) {
            return $content;
        }

        $sortedTags = $this->tagExtractor->extract($content);
        foreach ($sortedTags as $name => $tags) {
            $tags = $this->tagRegistry->getTag($name, 'html')->parseAll($tags, $locale);

            foreach ($tags as $tag => $newTag) {
                $content = str_replace($tag, $newTag, $content);
            }
        }

        return $this->parse($content, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($content, $locale)
    {
        $sortedTags = $this->tagExtractor->extract($content);
        if (0 === count($sortedTags)) {
            return [];
        }

        $result = [];
        foreach ($sortedTags as $name => $tags) {
            $result = array_merge(
                $result,
                $this->tagRegistry->getTag($name, 'html')->validateAll($tags, $locale)
            );
        }

        return $result;
    }
}
