<?php

namespace Sulu\Component\PHPCR\Wrapper;

/**
 * Enabes wrapper object to be set on (wrapped) objects.
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
interface WrapperAwareInterface
{
    /**
     * Set the wrapper
     *
     * @param WrapperInterface $wrapper
     */
    public function setWrapper(WrapperInterface $wrapper);

    /**
     * Return the Wrapper
     *
     * @return Wrapper
     */
    public function getWrapper();
}
