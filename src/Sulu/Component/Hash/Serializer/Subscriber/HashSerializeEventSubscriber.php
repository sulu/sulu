<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Hash\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Component\Hash\HasherInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Rest\ApiWrapper;

class HashSerializeEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var HasherInterface
     */
    private $hasher;

    public function __construct(HasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'],
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();

        // FIXME This can be removed, as soon as we've got rid of all ApiEntities.
        if ($object instanceof ApiWrapper) {
            $object = $object->getEntity();
        }

        if (!$object instanceof AuditableInterface) {
            return;
        }

        $event->getVisitor()->addData(
            '_hash',
            $this->hasher->hash($object)
        );
    }
}
