<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;

/**
 * The CategorySubscriber adds category-associated data to a serialized category, which cannot be serialized by the
 * category serializer configuration file.
 * This allows serializing a category entity in a proper format without the need of a wrapper class.
 */
class CategorySubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Adds translated data like associated translation and meta-entities and summarized data like creator, changer
     * and parent to a serialized category.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $category = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $groups = $context->attributes->get('groups')->getOrElse([]);

        if (!$category instanceof CategoryInterface) {
            return;
        }

        $locale = $context->attributes->get('locale')->getOrElse($category->getDefaultLocale());

        if (array_intersect(['fullCategory', 'partialCategory', 'contentTypeCategory'], $groups)) {
            $this->serializeMeta($category, $visitor, $context->shouldSerializeNull(), $locale);
            $this->serializeTranslation($category, $visitor, $context->shouldSerializeNull(), $locale);
        }

        if (array_intersect(['fullCategory', 'contentTypeCategory'], $groups)) {
            $this->serializeCreator($category, $visitor, $context->shouldSerializeNull());
            $this->serializeChanger($category, $visitor, $context->shouldSerializeNull());
        }

        if (array_intersect(['fullCategory'], $groups)) {
            $this->serializeParent($category, $visitor, $context->shouldSerializeNull());
        }
    }

    /**
     * Adds meta-related data in the respective locale to the serialization of a category.
     *
     * @param $category
     * @param $visitor
     * @param $serializeNull
     * @param $locale
     */
    private function serializeMeta($category, $visitor, $serializeNull, $locale)
    {
        if ($category->getMeta()) {
            $metaArray = [];
            foreach ($category->getMeta() as $meta) {
                if (!$meta->getLocale() || $meta->getLocale() === $locale) {
                    $metaArray[] = [
                        'id' => $meta->getId(),
                        'key' => $meta->getKey(),
                        'value' => $meta->getValue(),
                    ];
                }
            }

            $visitor->addData('meta', $metaArray);
        } elseif ($serializeNull) {
            $visitor->addData('meta', null);
        }
    }

    /**
     * Adds translation-related data in the respective locale to the serialization of a category.
     *
     * @param $category
     * @param $visitor
     * @param $serializeNull
     * @param $locale
     */
    private function serializeTranslation($category, $visitor, $serializeNull, $locale)
    {
        // fall back to default locale if requested locale does not exist
        $effectiveLocale = ($category->findTranslationByLocale($locale)) ? $locale : $category->getDefaultLocale();

        if ($translation = $category->findTranslationByLocale($effectiveLocale)) {
            $visitor->addData('name', $translation->getTranslation());
            $visitor->addData('locale', $translation->getLocale());
        } elseif ($serializeNull) {
            $visitor->addData('name', null);
            $visitor->addData('locale', null);
        }
    }

    /**
     * Adds a summary of the creator to the serialization of a category.
     *
     * @param $category
     * @param $visitor
     * @param $serializeNull
     */
    private function serializeCreator($category, $visitor, $serializeNull)
    {
        if ($category->getCreator()) {
            $visitor->addData('creator', $category->getCreator()->getFullName());
        } elseif ($serializeNull) {
            $visitor->addData('creator', null);
        }
    }

    /**
     * Adds a summary of the changer to the serialization of a category.
     *
     * @param $category
     * @param $visitor
     * @param $serializeNull
     */
    private function serializeChanger($category, $visitor, $serializeNull)
    {
        if ($category->getChanger()) {
            $visitor->addData('changer', $category->getChanger()->getFullName());
        } elseif ($serializeNull) {
            $visitor->addData('changer', null);
        }
    }

    /**
     * Adds the id of the parent of the category to the serialization of a category.
     *
     * @param $category
     * @param $visitor
     * @param $serializeNull
     */
    private function serializeParent($category, $visitor, $serializeNull)
    {
        if ($category->getParent()) {
            $visitor->addData('parent', $category->getParent()->getId());
        } elseif ($serializeNull) {
            $visitor->addData('parent', null);
        }
    }
}
