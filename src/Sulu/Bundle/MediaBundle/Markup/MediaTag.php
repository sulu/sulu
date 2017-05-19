<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Markup;

use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * Extends the sulu markup with the "sulu:link" tag.
 */
class MediaTag implements TagInterface
{
    const VALIDATE_REMOVED = 'removed';

    /**
     * @var string
     */
    protected static $entityName = 'SuluMediaBundle:Media';

    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @param MediaRepositoryInterface $mediaRepository
     * @param MediaManagerInterface $mediaManager
     */
    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        MediaManagerInterface $mediaManager
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->mediaManager = $mediaManager;
    }

    /**
     * {@inheritdoc}
     */
    public function parseAll(array $attributesByTag, $locale)
    {
        $medias = $this->preloadMedias($attributesByTag, $locale);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            $result[$tag] = $this->createMarkupForAttributes($attributes, $medias);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAll(array $attributesByTag, $locale)
    {
        $medias = $this->preloadMedias($attributesByTag, $locale);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            if (!array_key_exists($attributes['id'], $medias)) {
                $result[$tag] = self::VALIDATE_REMOVED;
            }
        }

        return $result;
    }

    /**
     * For given attributes of a tag, this method constructs the actual markup.
     *
     * @param $attributes array The attributes of a media tag
     * @param $medias array The array of medias corresponding the the ids of all passed attributes
     *
     * @return string The markup for the given attributes
     */
    private function createMarkupForAttributes($attributes, $medias)
    {
        if (!array_key_exists($attributes['id'], $medias)) {
            if (array_key_exists('content', $attributes)) {
                return $attributes['content'];
            }
            if (array_key_exists('title', $attributes)) {
                return $attributes['title'];
            }

            return '';
        }

        $media = $medias[$attributes['id']];
        $title = !empty($attributes['title']) ? $attributes['title'] : $media['title'];
        $text = !empty($attributes['content']) ? $attributes['content'] : $media['title'];

        if (empty($title)) {
            $title = $media['defaultTitle'];
        }

        return sprintf(
            '<a href="%s" title="%s">%s</a>',
            $media['url'],
            $title,
            $text
        );
    }

    /**
     * Return assets by id for given attributes.
     *
     * @param array $attributesByTag
     * @param string $locale
     *
     * @return array
     */
    private function preloadMedias($attributesByTag, $locale)
    {
        $ids = array_unique(
            array_values(
                array_map(
                    function ($attributes) {
                        return $attributes['id'];
                    },
                    $attributesByTag
                )
            )
        );

        $medias = $this->mediaRepository->findMediaDisplayInfo($ids, $locale);

        $result = [];
        foreach ($medias as $media) {
            $media['url'] = $this->mediaManager->getUrl($media['id'], $media['name'], $media['version']);
            $result[$media['id']] = $media;
        }

        return $result;
    }
}
