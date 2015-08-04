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
use Sulu\Component\SmartContent\Item;

/**
 * Represents item in content data provider.
 *
 * @ExclusionPolicy("all")
 */
class ContentDataItem extends Item
{
    /**
     * @var mixed
     */
    private $resource;

    public function __construct(array $data, $resource)
    {
        parent::__construct($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     *
     * @VirtualProperty()
     */
    public function getId()
    {
        return $this->get('uuid');
    }

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
     *
     * @VirtualProperty()
     */
    public function getFullQualifiedTitle()
    {
        return '/' . ltrim($this->get('path'), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
        return;
    }
}
