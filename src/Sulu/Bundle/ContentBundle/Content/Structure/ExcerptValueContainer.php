<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Structure;

use Sulu\Component\Util\ArrayableInterface;

class ExcerptValueContainer implements ArrayableInterface
{
    /**
     * @var array
     */
    private $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        if ($this->__isset($name)) {
            return $this->data[$name];
        } else {
            return;
        }
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        $result = [];
        foreach ($this->data as $key => $value) {
            if ($value instanceof ArrayableInterface) {
                $result[$key] = $value->toArray($depth);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
