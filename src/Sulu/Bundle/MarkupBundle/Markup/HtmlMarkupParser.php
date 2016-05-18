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
    const ATTRIBUTE_REGEX = '/(?<name>\b[\w-]+\b)\s*=\s*"(?<value>[^"]*)"/';
    const CONTENT_REGEX = '/(?:>(?<content>[^<]*)<)/';
    const TAG_REGEX = '/(?<tag><%s:(?<name>[a-z]+)[^\/>]*(?:\/>|>[^<]*<\/%s:[^\/>]*>))/';
    const INVALID_REGEX = '/(<%s:[a-z]+\b[^\/>]*)(\/>|>[^<]*<\/%s:[^\/>]*>)/';

    /**
     * @var TagRegistryInterface
     */
    private $tagRegistry;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @param TagRegistryInterface $tagRegistry
     * @param string $namespace
     */
    public function __construct(TagRegistryInterface $tagRegistry, $namespace = 'sulu')
    {
        $this->tagRegistry = $tagRegistry;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($content)
    {
        $sortedTags = $this->getTags($content);

        if (0 === count($sortedTags)) {
            return $content;
        }

        foreach ($sortedTags as $name => $tags) {
            $tags = $this->tagRegistry->getTag($name, 'html')->parseAll($tags);

            foreach ($tags as $tag => $newTag) {
                $content = str_replace($tag, $newTag, $content);
            }
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($content)
    {
        $sortedTags = $this->getTags($content);

        if (0 === count($sortedTags)) {
            return new ValidateResult(true, $content);
        }

        $valid = true;
        $regex = sprintf(self::INVALID_REGEX, $this->namespace, $this->namespace);
        foreach ($sortedTags as $name => $tags) {
            $validatedTags = $this->tagRegistry->getTag($name, 'html')->validateAll($tags);

            foreach ($validatedTags as $tag => $tagValid) {
                if ($tagValid) {
                    continue;
                }

                $valid = false;
                if (!array_key_exists('data-invalid', $tags[$tag]) || !$tags[$tag]['data-invalid']) {
                    $newTag = preg_replace($regex, '$1 data-invalid="true"$2', $tag);
                    $content = str_replace($tag, $newTag, $content);
                }
            }
        }

        return new ValidateResult($valid, $content);
    }

    /**
     * Returns found tags and their attributes.
     *
     * @param string $content
     *
     * @return array
     */
    private function getTags($content)
    {
        if (!preg_match_all(sprintf(self::TAG_REGEX, $this->namespace, $this->namespace), $content, $matches)) {
            return [];
        }

        $sortedTags = [];
        for ($i = 0, $length = count($matches['name']); $i < $length; ++$i) {
            $tag = $matches['tag'][$i];
            $name = $matches['name'][$i];
            if (!array_key_exists($name, $sortedTags)) {
                $sortedTags[$name] = [];
            }

            $sortedTags[$name][$tag] = $this->getAttributes($tag);
        }

        return $sortedTags;
    }

    /**
     * Returns attributes of given html-tag.
     *
     * @param string $tag
     *
     * @return array
     */
    private function getAttributes($tag)
    {
        if (!preg_match_all(self::ATTRIBUTE_REGEX, $tag, $matches)) {
            return [];
        }

        $attributes = [];
        for ($i = 0, $length = count($matches['name']); $i < $length; ++$i) {
            $value = $matches['value'][$i];

            if ($value === 'true' || $value === 'false') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            $attributes[$matches['name'][$i]] = $value;
        }

        if (preg_match(self::CONTENT_REGEX, $tag, $matches)) {
            $attributes['content'] = $matches['content'];
        }

        return $attributes;
    }
}
