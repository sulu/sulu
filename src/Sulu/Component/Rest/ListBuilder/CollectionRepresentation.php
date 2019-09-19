<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use JMS\Serializer\Annotation as Serializer;

/**
 * This class represents a list for our common rest services.
 *
 * @Serializer\XmlRoot("collection")
 */
class CollectionRepresentation
{
    /**
     * @Serializer\Exclude
     *
     * @var mixed
     */
    protected $data;

    /**
     * @Serializer\Exclude
     *
     * @var string
     */
    protected $rel;

    public function __construct($data, string $rel)
    {
        if (!is_array($data)) {
            $data = iterator_to_array($data);
        }

        $this->data = $data;
        $this->rel = $rel;
    }

    /**
     * @return mixed
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("_embedded")
     *
     * @return mixed[]
     */
    public function _embedded(): array
    {
        return [
            $this->rel => $this->data,
        ];
    }
}
