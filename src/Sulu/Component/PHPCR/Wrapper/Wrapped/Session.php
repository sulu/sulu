<?php

namespace Sulu\Component\PHPCR\Wrapper\Wrapped;

use Sulu\Component\PHPCR\Wrapper\WrapperAwareInterface;
use Sulu\Component\PHPCR\Wrapper\WrappedObjectInterface;
use Sulu\Component\PHPCR\Wrapper\Traits\SessionTrait;
use PHPCR\SessionInterface;

/**
 * The session wraps the PHPCR session and
 * uses the NodeMapper to return node type specific
 * objects if they exist.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class Session implements SessionInterface, WrapperAwareInterface, WrappedObjectInterface
{
    use SessionTrait;
}
