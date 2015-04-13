<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber\Compat;

use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\Content\Mapper\Event\ContentNodeDeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\Content\Mapper\ContentEvents;
use Sulu\Component\Util\SuluNodeHelper;

/**
 * Send the legacy content mapper NODE_PRE/POST_REMOVE events
 */
class MapperRemoveSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var array
     */
    private $events;

    public function __construct(
        DocumentInspector $inspector,
        EventDispatcherInterface $dispatcher,
        ContentMapperInterface $mapper,
        SuluNodeHelper $nodeHelper
    ) {
        $this->dispatcher = $dispatcher;
        $this->inspector = $inspector;
        $this->nodeHelper = $nodeHelper;
        $this->mapper = $mapper;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::REMOVE => array(
                array('handlePreRemove', 500),
                array('handlePostRemove', -100),
            ),
        );
    }

    public function handlePreRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $event = $this->getEvent($document);
        $this->events[spl_object_hash($document)] = $event;
        $this->structure[spl_object_hash($document)] = $event;
        $this->dispatcher->dispatch(
            ContentEvents::NODE_PRE_DELETE, 
            $event
        );
    }

    public function handlePostRemove(RemoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $oid = spl_object_hash($document);
        $event = $this->events[$oid];

        $this->dispatcher->dispatch(
            ContentEvents::NODE_POST_DELETE, 
            $event
        );

        unset($this->events[$oid]);
    }

    private function supports($document)
    {
        return $document instanceof ContentBehavior;
    }

    private function getEvent($document)
    {
        $webspace = $this->inspector->getWebspace($document);
        $event = new ContentNodeDeleteEvent(
            $this->mapper,
            $this->nodeHelper,
            $this->inspector->getNode($document),
            $webspace
        );

        return $event;
    }
}
