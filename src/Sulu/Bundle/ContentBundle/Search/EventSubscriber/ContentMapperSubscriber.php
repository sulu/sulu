<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventSubscriber;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Compat\Event\ContentNodeEvent;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\Content\Compat\Event\ContentNodeDeleteEvent;
use Sulu\Component\Content\Mapper\ContentEvents;

/**
 * Listen to sulu node save event and index the structure
 */
class ContentMapperSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ContentEvents::NODE_POST_SAVE => 'onNodeSave',
            ContentEvents::NODE_PRE_DELETE => 'onNodePreDelete',
            ContentEvents::NODE_POST_DELETE => 'onNodePostDelete',
        );
    }

    /**
     * @var SearchManagerInterface
     */
    protected $searchManager;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var StructureInterface[]
     */
    private $structuresToDeindex = array();

    /**
     * @param SearchManagerInterface $searchManager
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        SearchManagerInterface $searchManager,
        SessionManagerInterface $sessionManager
    ) {
        $this->searchManager = $searchManager;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Deindex/index structure in search implementation depending
     * on the publish state
     *
     * @param ContentNodeEvent $event
     */
    public function onNodeSave(ContentNodeEvent $event)
    {
        $structure = $event->getStructure();
        $this->searchManager->index($structure);
    }

    /**
     * Schedules a structure to be deindexed
     *
     * @param ContentNodeDeleteEvent
     */
    public function onNodePreDelete(ContentNodeDeleteEvent $event)
    {
        $structures = (array) $event->getStructures();
        $this->structuresToDeindex += $structures;
    }

    /**
     * Deindex any structures which have been deleted
     *
     * @param ContentNodeDeleteEvent
     */
    public function onNodePostDelete(ContentNodeDeleteEvent $event)
    {
        foreach ($this->structuresToDeindex as $structure) {
            $this->searchManager->deindex($structure);
        }
    }
}
