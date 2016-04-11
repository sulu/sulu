<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Translate;

use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;

class TranslateCollectionRepresentation extends CollectionRepresentation
{
    /**
     * @Exclude
     *
     * @var int
     */
    protected $total = null;

    /**
     * @VirtualProperty
     * @SerializedName("total")
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->total === null ? count($this->getResources()) : $this->total;
    }

    /**
     * @param $total
     *
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }
}
