<?php

namespace Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\EventListener;

use Sulu\Bundle\SearchBundle\Search\Event\StructureMetadataLoadEvent;

class StructureMetadataLoadListener
{
    public $structure;
    public $indexMetadata;

    public function handleStructureLoadMetadata(StructureMetadataLoadEvent $event)
    {
        $this->structure = $event->getStructure();
        $this->indexMetadata = $event->getIndexMetadata();
    }
}
