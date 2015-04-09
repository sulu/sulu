<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\Event\AbstractDocumentNodeEvent;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ContentSubscriber extends AbstractMappingSubscriber
{
    protected function supports($document)
    {
        return $document instanceof ContentBehavior;
    }

    /**
     * {@inheritDoc}
     */
    public function doHydrate(HydrateEvent $event)
    {
        $node = $event->getNode();
        $value = $node->getPropertyValueWithDefault(
            $this->encoder->localizedSystemName('template', $event->getLocale()),
            null
        );
        $event->getDocument()->setStructureType($value);
    }

    /**
     * {@inheritDoc}
     */
    public function doPersist(PersistEvent $event)
    {
        $node = $event->getNode();
        $node->setProperty(
            $this->encoder->localizedSystemName('template', $event->getLocale()),
            $event->getDocument()->getStructureType()
        );
    }
}
