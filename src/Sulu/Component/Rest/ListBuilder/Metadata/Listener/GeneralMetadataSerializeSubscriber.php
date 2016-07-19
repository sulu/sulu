<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Listener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;

/**
 * Extends field-descriptor serialization process.
 */
class GeneralMetadataSerializeSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * Add general metadata which is not present in the field-descriptor.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $fieldDescriptor = $event->getObject();
        $visitor = $event->getVisitor();

        if (!$fieldDescriptor instanceof FieldDescriptorInterface) {
            return;
        }

        $metadata = $fieldDescriptor->getMetadata();
        if (null === $metadata || !$metadata->has(PropertyMetadata::class)) {
            // this keeps BC because before this the type was used to determine the input-type.
            $visitor->addData('filter-type', $fieldDescriptor->getType());
            $visitor->addData('filter-type-parameters', []);

            return;
        }

        /** @var PropertyMetadata $propertyMetadata */
        $propertyMetadata = $metadata->get(PropertyMetadata::class);
        $visitor->addData('display', $propertyMetadata->getDisplay());

        if (null !== $propertyMetadata->getFilterType()) {
            $visitor->addData('filter-type', $propertyMetadata->getFilterType());
            $visitor->addData('filter-type-parameters', $propertyMetadata->getFilterTypeParameters());
        }
    }
}
