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
 * Extracts tags from html content.
 */
class HtmlTagExtractor implements TagExtractorInterface
{
    const COUNT_REGEX = '/<%1$s:[a-z]+/';
    const ATTRIBUTE_REGEX = '/(?<name>\b[\w-]+\b)\s*=\s*"(?<value>[^"]*)"/';
    const TAG_REGEX = '/(?<tag><%1$s:(?<name>[a-z]+)(?<attributes>(?:(?!>|\/>).)*)(?:\/>|>(?<content>(?:(?!<\/%1$s:\2>).)*)<\/%1$s:\2>))/s';

    /**
     * @var string
     */
    private $namespace;

    /**
     * @param string $namespace
     */
    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function count($html)
    {
        return preg_match_all(sprintf(self::COUNT_REGEX, $this->namespace), $html, $matches);
    }

    /**
     * {@inheritdoc}
     */
    public function extract($html)
    {
        if (!preg_match_all(sprintf(self::TAG_REGEX, $this->namespace), $html, $matches)) {
            return [];
        }

        /** @var TagMatchGroup[] $sortedTags */
        $sortedTags = [];
        for ($i = 0, $length = count($matches['name']); $i < $length; ++$i) {
            $tag = $matches['tag'][$i];
            $name = $matches['name'][$i];
            $content = $matches['content'][$i];
            if (!array_key_exists($name, $sortedTags)) {
                $sortedTags[$name] = new TagMatchGroup($this->namespace, $name);
            }

            $attributes = $this->getAttributes($matches['attributes'][$i]);
            $sortedTags[$name]->addTag($tag, array_filter(array_merge($attributes, ['content' => $content])));
        }

        return array_values($sortedTags);
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

        return $attributes;
    }
}
