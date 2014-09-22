<?php

namespace Sulu\Bundle\SearchBundle\Search\Event;

use Sulu\Component\Content\StructureInterface;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event which is fired when the Sulu Structure metadata driver
 * loads its metadata.
 */
class StructureMetadataLoadEvent extends Event
{
    private $structure;
    private $indexMetadata;

    /**
     * @param StructureInterface $structure
     * @param IndexMetadata $indexMetadata
     */
    public function __construct(StructureInterface $structure, IndexMetadata $indexMetadata)
    {
        $this->structure = $structure;
        $this->indexMetadata = $indexMetadata;
    }

    /**
     * Return the Structure for which the metadata is being loaded
     *
     * @return StructureInterface
     */
    public function getStructure() 
    {
        return $this->structure;
    }

    /**
     * Return the metadata class which has been loaded for the Structure
     *
     * @return IndexMetadata
     */
    public function getIndexMetadata() 
    {
        return $this->indexMetadata;
    }
}
