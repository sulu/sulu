<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Event;

use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadata;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event which is fired when the Sulu Structure metadata driver
 * loads its metadata.
 */
class StructureMetadataLoadEvent extends Event
{
    public function __construct(
        private StructureInterface $structure,
        private IndexMetadata $indexMetadata
    ) {
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
