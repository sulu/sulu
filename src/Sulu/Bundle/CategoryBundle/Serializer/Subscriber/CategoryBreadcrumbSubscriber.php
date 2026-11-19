<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;

/**
 * Adds the path of a category, e.g. "Shop > Shirts", to its serialization.
 *
 * It needs a query for the ancestors, so a controller has to ask for it with the serialization group.
 * Only the serialized root object gets it, not the children inside of it.
 */
class CategoryBreadcrumbSubscriber implements EventSubscriberInterface
{
    public const GROUP = 'category_breadcrumb';

    private const SEPARATOR = ' > ';

    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    /**
     * @return array<array{event: string, method: string, class: class-string}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize', 'class' => Category::class],
        ];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $context = $event->getContext();

        if (!$context->hasAttribute('groups')
            || !\in_array(self::GROUP, (array) $context->getAttribute('groups'), true)
            // the visited object is already off the stack here, so only the root object is at depth 0
            || $context->getDepth() > 0
        ) {
            return;
        }

        $category = $event->getObject();
        $visitor = $event->getVisitor();
        if (!$category instanceof Category || !$visitor instanceof SerializationVisitorInterface) {
            return;
        }

        $entity = $category->getEntity();
        if (!$entity instanceof CategoryInterface) {
            return;
        }

        $locale = $category->getLocale();

        $names = [];
        foreach ($this->categoryRepository->findAncestorCategories($entity) as $ancestor) {
            $names[] = (new Category($ancestor, $locale))->getName();
        }
        $names[] = $category->getName();

        // a category without any translation has no name
        $breadcrumb = \implode(self::SEPARATOR, \array_filter($names, static fn (?string $name): bool => null !== $name));

        $visitor->visitProperty(new StaticPropertyMetadata('', 'breadcrumb', $breadcrumb), $breadcrumb);
    }
}
