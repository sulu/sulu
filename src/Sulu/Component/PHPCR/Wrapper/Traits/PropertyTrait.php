<?php

namespace Sulu\Component\PHPCR\Wrapper\Traits;

/**
 * This trait fulfils the PHPCR\PropertyInterface
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
trait PropertyTrait
{
    use ItemTrait;

    public function setValue($value, $type = null)
    {
        return $this->call('setValue', func_get_args());
    }

    public function addValue($value)
    {
        return $this->call('addValue', func_get_args());
    }

    public function getValue()
    {
        return $this->call('getValue');
    }

    public function getString()
    {
        return $this->call('getString');
    }

    public function getBinary()
    {
        return $this->call('getBinary');
    }

    public function getLong()
    {
        return $this->call('getLong');
    }

    public function getDouble()
    {
        return $this->call('getDouble');
    }

    public function getDecimal()
    {
        return $this->call('getDecimal');
    }

    public function getDate()
    {
        return $this->call('getDate');
    }

    public function getBoolean()
    {
        return $this->call('getBoolean');
    }

    public function getNode()
    {
        return $this->getWrapper()->wrap($this->call('getNode'), 'PHPCR\NodeInterface');
    }

    public function getProperty()
    {
        return $this->getWrapper()->wrap($this->call('getProperty'), 'PHPCR\PropertyInterface');
    }

    public function getLength()
    {
        return $this->call('getLength');
    }

    public function getDefinition()
    {
        return $this->getWrapper()->wrap($this->call('getDefinition'), 'PHPCR\PropertyDefinitionInterface');
    }

    public function getType()
    {
        return $this->call('getType');
    }

    public function isMultiple()
    {
        return $this->call('isMultiple');
    }
}
