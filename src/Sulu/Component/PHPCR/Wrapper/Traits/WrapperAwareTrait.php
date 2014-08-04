<?php

namespace Sulu\Component\PHPCR\Wrapper\Traits;

use Sulu\Component\PHPCR\Wrapper\WrapperInterface;

trait WrapperAwareTrait
{
    protected $wrapper;

    public function setWrapper(WrapperInterface $wrapper)
    {
        $this->wrapper = $wrapper;
    }

    public function getWrapper()
    {
        return $this->wrapper;
    }
}
