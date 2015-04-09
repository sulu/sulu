<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\EventListener;

use Massive\Bundle\SearchBundle\Search\Event\SearchEvent;
use Sulu\Component\Content\Structure\Factory\StructureFactoryInterface;

/**
 * Listen to for search to be sure that all structure cache classes are generated and loaded
 */
class SearchListener
{
    /**
     * @var StructureFactoryInterface
     */
    private $structureFactory;

    public function __construct(StructureFactoryInterface $structureFactory)
    {
        $this->structureFactory = $structureFactory;
    }

    /**
     * Generate all Structures to be sure that all hits can be handled correctly
     * @param SearchEvent $event
     */
    public function onSearch(SearchEvent $event)
    {
        $this->structureFactory->getStructures();
    }
}
