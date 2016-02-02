<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
