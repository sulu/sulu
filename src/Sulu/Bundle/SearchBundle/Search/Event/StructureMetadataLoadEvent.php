<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Event;

use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event which is fired when the Sulu Structure metadata driver
 * loads its metadata.
 */
class StructureMetadataLoadEvent extends Event
{
    /**
     * The structure, which has been loaded for indexing.
     *
     * @var StructureInterface
     */
    private $structure;

    /**
     * The metadata based on which the data has been loaded.
     *
     * @var IndexMetadata
     */
    private $indexMetadata;

    /**
     * @param StructureInterface $structure
     * @param IndexMetadata      $indexMetadata
     */
    public function __construct(StructureInterface $structure, IndexMetadata $indexMetadata)
    {
        $this->structure = $structure;
        $this->indexMetadata = $indexMetadata;
    }

    /**
     * Return the Structure for which the metadata is being loaded.
     *
     * @return StructureInterface
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Return the metadata class which has been loaded for the Structure.
     *
     * @return IndexMetadata
     */
    public function getIndexMetadata()
    {
        return $this->indexMetadata;
    }
}
