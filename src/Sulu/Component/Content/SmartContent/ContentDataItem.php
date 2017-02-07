<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * Represents item in content data provider.
 *
 * @ExclusionPolicy("all")
 */
class ContentDataItem extends ArrayAccessItem implements ItemInterface
{
    /**
     * @param array $data
     * @param object $resource
     */
    public function __construct(array $data, $resource)
    {
        parent::__construct($data['uuid'], $data, $resource);
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty
     */
    public function getTitle()
    {
        return $this->get('title');
    }

    /**
     * Returns the date at which the content was published.
     *
     * @return \DateTime
     *
     * @VirtualProperty
     */
    public function getPublished()
    {
        return $this->get('published');
    }

    /**
     * Returns true iff the latest version of the content is published.
     *
     * @return bool
     *
     * @VirtualProperty
     */
    public function getPublishedState()
    {
        return $this->get('publishedState');
    }

    /**
     * Returns the url of the content item.
     *
     * @return string
     *
     * @VirtualProperty
     */
    public function getUrl()
    {
        return $this->get('url');
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
        return;
    }
}
