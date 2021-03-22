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

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderPoolInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Symfony\Component\HttpFoundation\UrlHelper;

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
     * @var bool
     */
    private $isPreview;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    public function __construct(
        LinkProviderPoolInterface $linkProviderPool,
        bool $isPreview = false,
        UrlHelper $urlHelper = null
    ) {
        $this->linkProviderPool = $linkProviderPool;
        $this->isPreview = $isPreview;
        $this->urlHelper = $urlHelper;

        if (null === $this->urlHelper) {
            @\trigger_error(
                'Instantiating the LinkTag class without the $urlHelper argument is deprecated.',
                \E_USER_DEPRECATED
            );
        }
    }

    public function parseAll(array $attributesByTag, $locale)
    {
        $contents = $this->preload($attributesByTag, $locale);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            $validationState = $attributes['sulu-validation-state'] ?? null;

            if (isset($attributes['href']) && \array_key_exists($provider . '-' . $attributes['href'], $contents)) {
                $item = $contents[$provider . '-' . $attributes['href']];

                $url = $item->getUrl();
                if ($this->urlHelper) {
                    $url = $this->urlHelper->getAbsoluteUrl($url);
                }

                $title = $item->getTitle();
                $attributes['href'] = $url;
                $attributes['title'] = $this->getValue($attributes, 'title', $item->getTitle());
            } elseif ($this->isPreview && self::VALIDATE_UNPUBLISHED === $validationState) {
                // render anchor without href to keep styling even if target is not published in preview
                $title = $this->getContent($attributes);
                $attributes['href'] = null;
                $attributes['title'] = $this->getValue($attributes, 'title');
            } else {
                // only render text instead of anchor to prevent dead links on website
                $result[$tag] = $this->getContent($attributes);

                continue;
            }

            $htmlAttributes = \array_map(
                function ($value, $name) {
                    if (\in_array($name, ['provider', 'content', 'sulu-validation-state']) || empty($value)) {
                        return;
                    }

                    return \sprintf('%s="%s"', $name, $value);
                },
                $attributes,
                \array_keys($attributes)
            );

            $result[$tag] = \sprintf(
                '<a %s>%s</a>',
                \implode(' ', \array_filter($htmlAttributes)),
                $this->getValue($attributes, 'content', $title)
            );
        }

        return $result;
    }

    public function validateAll(array $attributesByTag, $locale)
    {
        $items = $this->preload($attributesByTag, $locale, false);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $provider = $this->getValue($attributes, 'provider', self::DEFAULT_PROVIDER);
            if (!isset($attributes['href'])
                || !\array_key_exists($provider . '-' . $attributes['href'], $items)
            ) {
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
            if (!\array_key_exists($provider, $hrefsByType)) {
                $hrefsByType[$provider] = [];
            }

            if (isset($attributes['href'])) {
                $hrefsByType[$provider][] = $attributes['href'];
            }
        }

        $result = [];
        foreach ($hrefsByType as $provider => $hrefs) {
            $items = $this->linkProviderPool->getProvider($provider)->preload(
                \array_unique($hrefs),
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
     * @param string $name
     */
    private function getValue(array $attributes, $name, $default = null)
    {
        if (\array_key_exists($name, $attributes) && !empty($attributes[$name])) {
            return $attributes[$name];
        }

        return $default;
    }

    /**
     * Returns content or title of given attributes.
     *
     * @return string
     */
    private function getContent(array $attributes)
    {
        if (\array_key_exists('content', $attributes)) {
            return $attributes['content'];
        }

        return $this->getValue($attributes, 'title', '');
    }
}
