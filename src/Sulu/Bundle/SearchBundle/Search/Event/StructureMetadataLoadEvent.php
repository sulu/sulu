<?php

namespace Sulu\Bundle\SearchBundle\Search\Event;

use Sulu\Component\Content\StructureInterface;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Symfony\Component\EventDispatcher\Event;

class StructureMetadataLoadEvent extends Event
{
    private $structure;
    private $indexMetadata;

    public function __construct(StructureInterface $structure, IndexMetadata $indexMetadata)
    {
        $this->structure = $structure;
        $this->indexMetadata = $indexMetadata;
    }

    public function getStructure() 
    {
        return $this->structure;
    }

    public function getIndexMetadata() 
    {
        return $this->indexMetadata;
    }
}
