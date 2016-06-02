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

use Sulu\Bundle\MarkupBundle\Markup\MarkupParserInterface;
use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;

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
     * @var DoctrineListBuilderFactory
     */
    private $listBuilderFactory;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @param DoctrineListBuilderFactory $listBuilderFactory
     * @param FieldDescriptorFactoryInterface $fieldDescriptorFactory
     * @param MediaManagerInterface $mediaManager
     */
    public function __construct(
        DoctrineListBuilderFactory $listBuilderFactory,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        MediaManagerInterface $mediaManager
    ) {
        $this->listBuilderFactory = $listBuilderFactory;
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
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
            if (!array_key_exists($attributes['id'], $medias)) {
                $result[$tag] = array_key_exists('content', $attributes) ? $attributes['content'] :
                    (array_key_exists('title', $attributes) ? $attributes['title'] : '');

                continue;
            }

            $media = $medias[$attributes['id']];
            $title = !empty($attributes['title']) ? $attributes['title'] : $media['title'];
            $text = !empty($attributes['content']) ? $attributes['content'] : $media['title'];

            $result[$tag] = sprintf(
                '<a href="%s" title="%s">%s</a>',
                $media['url'],
                $title,
                $text
            );
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
            if(!array_key_exists($attributes['id'], $medias)){
                $result[$tag] = self::VALIDATE_REMOVED;
            }
        }

        return $result;
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

        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptorForClass(
            Media::class,
            ['locale' => $locale]
        );

        $listBuilder = $this->listBuilderFactory->create(self::$entityName);
        $listBuilder->setFieldDescriptors($fieldDescriptors);
        $listBuilder->in($fieldDescriptors['id'], $ids);
        $listBuilder->limit(count($ids));

        $listBuilder->addSelectField($fieldDescriptors['id']);
        $listBuilder->addSelectField($fieldDescriptors['version']);
        $listBuilder->addSelectField($fieldDescriptors['name']);
        $listBuilder->addSelectField($fieldDescriptors['title']);

        $medias = $listBuilder->execute();

        $result = [];
        foreach ($medias as $media) {
            $media['url'] = $this->mediaManager->getUrl($media['id'], $media['name'], $media['version']);
            $result[$media['id']] = $media;
        }

        return $result;
    }
}
