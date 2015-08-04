<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Repository;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\SmartContent\Item;

/**
 * Represents item in content data provider.
 *
 * @ExclusionPolicy("all")
 */
class ContactDataItem extends Item
{
    /**
     * {@inheritdoc}
     *
     * @VirtualProperty()
     */
    public function getId()
    {
        return $this->get('id');
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty()
     */
    public function getTitle()
    {
        return $this->get('name');
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty()
     */
    public function getFullQualifiedTitle()
    {
        return $this->get('name');
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty()
     */
    public function getImage()
    {
        return null;
    }
}
