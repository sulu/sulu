<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Metadata\Listener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Bundle\ResourceBundle\Resource\Metadata\PropertyMetadata;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;

/**
 * Extends field-descriptor serialization process.
 */
class FilterMetadataSerializeSubscriber implements EventSubscriberInterface
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
     * Add filter metadata.
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
        if ($metadata === null) {
            $visitor->addData('filter:input-type', $fieldDescriptor->getType());
        } elseif ($metadata->has(PropertyMetadata::class)) {
            $filterMetadata = $metadata->get(PropertyMetadata::class);
            $visitor->addData('filter:input-type', $filterMetadata->getInputType());
            $visitor->addData('filter:parameters', $filterMetadata->getParameters());
        }
    }
}
