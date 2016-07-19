<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Rest;

use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Provides a collection of items and the datasource.
 *
 * @ExclusionPolicy("all")
 */
class ItemCollectionRepresentation extends CollectionRepresentation
{
    /**
     * @var ItemInterface
     */
    private $datasource;

    /**
     * @var int
     */
    private $total;

    public function __construct(array $items, $datasource)
    {
        parent::__construct($items, 'items');

        $this->datasource = $datasource;
        $this->total = count($items);
    }

    /**
     * Returns datasource of smart content item collection.
     *
     * @return ItemInterface
     *
     * @VirtualProperty()
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     * Returns amount of items.
     *
     * @return int
     *
     * @VirtualProperty()
     */
    public function getTotal()
    {
        return $this->total;
    }
}
