<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventSubscriber;

use Sulu\Component\Content\ContentEvents;
use Sulu\Component\Content\Event\ContentNodeDeleteEvent;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Sulu\Component\HttpCache\HandlerInvalidateStructureInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listen to the content mapper and invalidate structures.
 */
class ContentMapperSubscriber implements EventSubscriberInterface
{
    /**
     * @var Sulu\Component\HttpCache\HandlerInvalidateStructureInterface
     */
    private $handler;

    /**
     * @var StructureInterface[]
     */
    private $structureInvalidationStack = array();

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ContentEvents::NODE_POST_SAVE => 'onContentNodePostSave',
            ContentEvents::NODE_PRE_DELETE => 'onContentNodePreDelete',
            ContentEvents::NODE_POST_DELETE => 'onContentNodePostDelete',
        );
    }

    /**
     * @param HandlerInvalidateStructureInterface $handler
     */
    public function __construct(HandlerInvalidateStructureInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param ContentNodeEvent $event
     */
    public function onContentNodePostSave(ContentNodeEvent $event)
    {
        $this->handler->invalidateStructure($event->getStructure());
    }

    /**
     * @param ContentNodeDeleteEvent
     */
    public function onContentNodePreDelete(ContentNodeDeleteEvent $event)
    {
        foreach ($event->getStructures() as $structure) {
            $this->structureInvalidationStack[] = $structure;

            // we do not need to iterate over all the languages. one is enough.
            return;
        }
    }

    /**
     * @param ContentNodeDeleteEvent
     */
    public function onContentNodePostDelete(ContentNodeDeleteEvent $event)
    {
        foreach ($this->structureInvalidationStack as $structure) {
            $this->handler->invalidateStructure($structure);
        }
    }
}
