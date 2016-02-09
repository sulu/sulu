<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type;

use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;

/**
 * Describes a normal field.
 */
class SingleType extends PropertyType implements \Serializable
{
    /**
     * @var FieldMetadata
     */
    private $field;

    public function __construct(FieldMetadata $field)
    {
        $this->field = $field;
    }

    /**
     * Returns metadata for field.
     *
     * @return FieldMetadata
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->field]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->field) = unserialize($serialized);
    }
}
