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

    public function __construct(TagRegistryInterface $tagRegistry, TagExtractorInterface $tagExtractor)
    {
        $this->tagRegistry = $tagRegistry;
        $this->tagExtractor = $tagExtractor;
    }

    public function parse($content, $locale)
    {
        if (0 === $this->tagExtractor->count($content)) {
            return $content;
        }

        $tagMatchGroups = $this->tagExtractor->extract($content);
        foreach ($tagMatchGroups as $tagMatchGroup) {
            $tags = $this->tagRegistry->getTag($tagMatchGroup->getTagName(), $this->getType(), $tagMatchGroup->getNamespace())
                ->parseAll($tagMatchGroup->getTags(), $locale);

            $content = str_replace(array_keys($tags), array_values($tags), $content);
        }

        return $this->parse($content, $locale);
    }

    public function validate($content, $locale)
    {
        if (0 === $this->tagExtractor->count($content)) {
            return [];
        }

        $result = [];
        $tagMatchGroups = $this->tagExtractor->extract($content);
        foreach ($tagMatchGroups as $tagMatchGroup) {
            $tags = $this->tagRegistry->getTag($tagMatchGroup->getTagName(), $this->getType(), $tagMatchGroup->getNamespace())
                ->validateAll($tagMatchGroup->getTags(), $locale);

            $result = array_merge($result, $tags);
        }

        return $result;
    }

    public function getType(): string
    {
        return 'html';
    }
}
