<?php

namespace Sulu\Bundle\SearchBundle\EventListener;

use Massive\Bundle\SearchBundle\Search\Event\SearchEvent;
use Sulu\Component\Content\StructureManagerInterface;

/**
 * Listen to for search to be sure that all structure cache classes are generated and loaded
 */
class SearchListener
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    public function __construct(StructureManagerInterface $structureManager)
    {
        $this->structureManager = $structureManager;
    }

    /**
     * Generate all Structures to be sure that all hits can be handled correctly
     * @param SearchEvent $event
     */
    public function onSearch(SearchEvent $event)
    {
        $this->structureManager->getStructures();
    }
}
