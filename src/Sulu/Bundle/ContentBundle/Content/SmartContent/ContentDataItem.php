<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\SmartContent\Item;

/**
 * Represents item in content data provider.
 *
 * @ExclusionPolicy("all")
 */
class ContentDataItem extends Item
{
    /**
     * {@inheritdoc}
     *
     * @VirtualProperty()
     */
    public function getTitle()
    {
        return $this->get('title');
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
        return null;
    }
}
