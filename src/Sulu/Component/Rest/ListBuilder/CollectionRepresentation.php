<?php

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
