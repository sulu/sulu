<?php

namespace Sulu\Bundle\SearchBundle\EventListener;

use Sulu\Component\Content\Event\ContentNodeEvent;
use Massive\Bundle\SearchBundle\Search\SearchManager;

class NodeSaveListener
{
    protected $searchManager;

    public function __construct(SearchManager $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    public function onNodeSave(ContentNodeEvent $event)
    {
        $structure = $event->getStructure();
        $this->searchManager->index($structure);
    }
}
