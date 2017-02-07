<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Markup;

use Sulu\Bundle\ContentBundle\Markup\Link\LinkItem;
use Sulu\Bundle\ContentBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;

/**
 * Extends the sulu markup with the "sulu:link" tag.
 */
class LinkTag implements TagInterface
{
    const VALIDATE_UNPUBLISHED = 'unpublished';
    const VALIDATE_REMOVED = 'removed';
    const DEFAULT_PROVIDER = 'page';

    /**
     * @var LinkProviderPoolInterface
     */
    private $linkProviderPool;

    /**
     * @param LinkProviderPoolInterface $linkProviderPool
     */
    public function __construct(LinkProviderPoolInterface $linkProviderPool)
    {
        $this->linkProviderPool = $linkProviderPool;
    }

    /**
     * {@inheritdoc}
     */
    public function parseAll(array $attributesByTag, $locale)
    {
        $contents = $this->preload($attributesByTag, $locale);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            if (!array_key_exists($provider . '-' . $attributes['href'], $contents)) {
                $result[$tag] = $this->getContent($attributes);

                continue;
            }

            $item = $contents[$provider . '-' . $attributes['href']];
            $result[$tag] = sprintf(
                '<a href="%s" title="%s"%s>%s</a>',
                $item->getUrl(),
                $this->getValue($attributes, 'title', $item->getTitle()),
                (!empty($attributes['target']) ? ' target="' . $attributes['target'] . '"' : ''),
                $this->getValue($attributes, 'content', $item->getTitle())
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAll(array $attributesByTag, $locale)
    {
        $items = $this->preload($attributesByTag, $locale, false);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            if (!array_key_exists($provider . '-' . $attributes['href'], $items)) {
                $result[$tag] = self::VALIDATE_REMOVED;
            } elseif (!$items[$provider . '-' . $attributes['href']]->isPublished()) {
                $result[$tag] = self::VALIDATE_UNPUBLISHED;
            }
        }

        return $result;
    }

    /**
     * Return items for given attributes.
     *
     * @param array $attributesByTag
     * @param string $locale
     * @param bool $published
     *
     * @return LinkItem[]
     */
    private function preload($attributesByTag, $locale, $published = true)
    {
        $hrefsByType = [];
        foreach ($attributesByTag as $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            if (!array_key_exists($provider, $hrefsByType)) {
                $hrefsByType[$provider] = [];
            }

            $hrefsByType[$provider][] = $attributes['href'];
        }

        $result = [];
        foreach ($hrefsByType as $provider => $hrefs) {
            $items = $this->linkProviderPool->getProvider($provider)->preload(
                array_unique($hrefs),
                $locale,
                $published
            );

            foreach ($items as $item) {
                $result[$provider . '-' . $item->getId()] = $item;
            }
        }

        return $result;
    }

    /**
     * Returns attribute identified by name or default if not exists.
     *
     * @param array $attributes
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    private function getValue(array $attributes, $name, $default = null)
    {
        if (array_key_exists($name, $attributes) && !empty($attributes[$name])) {
            return $attributes[$name];
        }

        return $default;
    }

    /**
     * Returns content or title of given attributes.
     *
     * @param array $attributes
     *
     * @return string
     */
    private function getContent(array $attributes)
    {
        if (array_key_exists('content', $attributes)) {
            return $attributes['content'];
        }

        return $this->getValue($attributes, 'title', '');
    }
}
