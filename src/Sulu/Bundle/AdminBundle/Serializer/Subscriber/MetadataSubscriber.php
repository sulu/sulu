<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class MetadataSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<array{
     *     event: string,
     *     format: string,
     *     method: string,
     *     class: class-string,
     * }>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerializeFormMetadata',
                'class' => FormMetadata::class,
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerializeItemMetadata',
                'class' => ItemMetadata::class,
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerializeItemMetadata',
                'class' => FieldMetadata::class,
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerializeItemMetadata',
                'class' => SectionMetadata::class,
            ],
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerializeOptionMetadata',
                'class' => OptionMetadata::class,
            ],
        ];
    }

    public function onPostSerializeFormMetadata(ObjectEvent $event): void
    {
        /** @var FormMetadata $formMetadata */
        $formMetadata = $event->getObject();
        $groups = $this->extractGroups($event);
        $locale = $this->extractLocale($event);

        if (null === $locale
            || \in_array('admin_form_metadata_keys_only', $groups)
        ) {
            return;
        }

        $this->addProperties($event, [
            'title' => $formMetadata->getTitle($locale),
        ]);
    }

    public function onPostSerializeItemMetadata(ObjectEvent $event): void
    {
        /** @var ItemMetadata $itemMetadata */
        $itemMetadata = $event->getObject();
        $groups = $this->extractGroups($event);
        $locale = $this->extractLocale($event);

        if (null === $locale
            || \in_array('admin_form_metadata_keys_only', $groups)
        ) {
            return;
        }

        $this->addProperties($event, [
            'label' => $itemMetadata->getLabel($locale),
            'description' => $itemMetadata->getDescription($locale),
        ]);
    }

    public function onPostSerializeOptionMetadata(ObjectEvent $event): void
    {
        /** @var OptionMetadata $optionMetadata */
        $optionMetadata = $event->getObject();
        $groups = $this->extractGroups($event);
        $locale = $this->extractLocale($event);

        if (null === $locale
            || \in_array('admin_form_metadata_keys_only', $groups)
        ) {
            return;
        }

        $this->addProperties($event, [
            'title' => $optionMetadata->getTitle($locale),
            'infoText' => $optionMetadata->getInfoText($locale),
            'placeholder' => $optionMetadata->getPlaceholder($locale),
        ]);
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function addProperties(ObjectEvent $event, array $properties): void
    {
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();

        foreach ($properties as $name => $value) {
            if (null === $value) {
                continue;
            }

            $visitor->visitProperty(new StaticPropertyMetadata('', $name, $value), $value);
            $visitor->setData($name, $value);
        }
    }

    /**
     * @return array<mixed>
     */
    private function extractGroups(ObjectEvent $event): array
    {
        $groups = $event->getContext()->getAttribute('groups');

        return \is_array($groups) ? $groups : [];
    }

    private function extractLocale(ObjectEvent $event): ?string
    {
        $locale = $event->getContext()->getAttribute('locale');

        return \is_string($locale) ? $locale : null;
    }
}
