<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Component\SmartContent\Exception\NoSuchPropertyException;
use Sulu\Component\SmartContent\Exception\NotSupportedException;

/**
 * Base class for DataProvider items.
 *
 * @ExclusionPolicy("all")
 */
abstract class ArrayAccessItem implements ResourceItemInterface, \ArrayAccess
{
    /**
     * @var array
     */
    private $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns TRUE if data array contains given key.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function exists($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Returns value with given key.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws NoSuchPropertyException
     */
    protected function get($key)
    {
        if (!$this->exists($key)) {
            throw new NoSuchPropertyException();
        }

        return $this->data[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new NotSupportedException();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new NotSupportedException();
    }
}
