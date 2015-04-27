<?php

namespace Sulu\Component\Content\Document\Extension;

use Sulu\Component\Content\Extension\ExtensionManager;
use PHPCR\NodeInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

class ExtensionContainer implements \ArrayAccess, \Iterator
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($extensionName)
    {
        if (isset($this->data[$extensionName])) {
            return $this->data[$extensionName];
        }

        return null;
    }

    public function offsetSet($extensionName, $data)
    {
        $this->data[$extensionName] = $data;
    }

    public function offsetUnset($extensionName)
    {
        unset($this->data[$extensionName]);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function current()
    {
        return current($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function next()
    {
        return next($this->data);
    }

    public function rewind()
    {
        return reset($this->data);
    }

    public function valid()
    {
        return isset($this->data) !== null;
    }

}
